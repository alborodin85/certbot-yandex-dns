<?php

namespace It5;

use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\CertUpdateSteps\CheckCertNeedUpdate\CertDeadlineChecker;
use It5\Localization\Trans;
use It5\LongProcesses\DnsParameterWaiter\WaiterSomeDnsRecords;
use It5\ParametersParser\CliParametersRegistry;
use It5\ParametersParser\DomainParametersDto;
use It5\ParametersParser\DomainsParametersRegistry;
use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use It5\Adapters\YandexApi\YandexDnsApi;

class CertbotYandexDns
{
    private static self $selfInstance;

    private bool $isQuiet;
    private CertDeadlineChecker $certDeadlineChecker;
    private YandexDnsApi $yandexDnsApi;
    private CertbotDialog $certbotDialog;
    private WaiterSomeDnsRecords $waiterDnsRecords;

    public static function singleton(
        array $cliArgv,
        string $configAbsolutePath,
        string $logAbsolutePath,
    ): self {
        if (empty(self::$selfInstance)) {
            self::$selfInstance = new self($cliArgv, $configAbsolutePath, $logAbsolutePath);
        }

        return self::$selfInstance;
    }

    private function __construct(
        private $cliArgv,
        private string $configAbsolutePath,
        private string $logAbsolutePath,
    ) {
        $this->initConfigs();
        $this->certDeadlineChecker = new CertDeadlineChecker();
        $this->yandexDnsApi = new YandexDnsApi(Env::env()->yandexApiDelayMicroseconds);
        $this->certbotDialog = new CertbotDialog();
        $this->waiterDnsRecords = new WaiterSomeDnsRecords(
            Env::env()->maxWaitingSpreadingSeconds,
            Env::env()->testingSpreadingIntervalSeconds,
            Env::env()->googleDnsServerIp,
        );
    }

    public function setDeadlineCheckerMock(CertDeadlineChecker $checkerMock): void
    {
        $this->certDeadlineChecker = $checkerMock;
    }

    public function setYandexDnsApiMock(YandexDnsApi $yandexDnsApiMock)
    {
        $this->yandexDnsApi = $yandexDnsApiMock;
    }

    public function setCertbotDialogMock(CertbotDialog $certbotDialogMock)
    {
        $this->certbotDialog = $certbotDialogMock;
    }

    public function setDnsWaiterMock(WaiterSomeDnsRecords $waiterDnsRecordsMock)
    {
        $this->waiterDnsRecords = $waiterDnsRecordsMock;
    }

    public function renewCerts(): bool
    {
        // Прочитать конфиг
        $domains = DomainsParametersRegistry::getCollection();
        // Запустить обновление для каждого домена из конфига
        foreach ($domains as $domainDto) {
            $result = $this->updateCertForOneDomain($domainDto);
            DebugLib::dump($result);
        }

        return true;
    }

    private function updateCertForOneDomain(DomainParametersDto $domainDto): bool
    {
        // Проверить срок сертификата; если добавили поддоменов - принудительно обновить
        if (!$this->certDeadlineChecker->checkDeadline($domainDto->certPath, $domainDto->criticalRemainingDays)) {
            return false;
        }
        // Начать диалог с CertBot
        $dialogDto = $this->certbotDialog->openDialog($domainDto, DebugLib::singleton()->logFile);
        // Получить из диалога требуемые DNS-параметры
        $arDnsRecords = $this->certbotDialog->getRequiredDnsRecords($dialogDto, $domainDto);
        // Проверить, нет ли в зоне параметров; если есть - удалить
        // Создать в зоне требуемые параметры
        $createdRecords = new DnsRecordsCollection();
        foreach ($arDnsRecords as $arRecord) {
            foreach ($arRecord as $subDomain => $recordText) {
                $this->yandexDnsApi->delete(
                    $domainDto->domain, $domainDto->yandexToken, $subDomain, DnsRecordTypesEnum::TXT
                );
                $createdRecords[] = $this->yandexDnsApi->create(
                    $domainDto->domain, $domainDto->yandexToken, $subDomain, DnsRecordTypesEnum::TXT, $recordText
                );
            }
        }

        DebugLib::dump(['$arDnsRecords', $arDnsRecords]);
        DebugLib::dump(['$createdRecords', $createdRecords]);

        // Дождаться, пока зона распространится
        $waitingResult = $this->waiterDnsRecords->waitingSomeParameters($createdRecords);
        if(!$waitingResult) {
            $errorMessage = Trans::T(
                'errors.dns-not-spread',
                implode("; ", $domainDto->subDomains),
                Env::env()->maxWaitingSpreadingSeconds,
            );
            DebugLib::ld($errorMessage);
            $this->certbotDialog->closeDialog($dialogDto);
            $this->deleteCreatedDnsRecords($createdRecords, $domainDto);

            return false;
        }
        // Дать последний Enter CertBot
        $this->certbotDialog->startCheckingAndGetResult($dialogDto);

        // Закончить диалог с CertBot
        $this->certbotDialog->closeDialog($dialogDto);

        // Удалить добавленные DNS-записи
        $this->deleteCreatedDnsRecords($createdRecords, $domainDto);

        return true;
    }

    private function deleteCreatedDnsRecords(
        DnsRecordsCollection $createdRecords,
        DomainParametersDto $domainDto,
    ): void {
        foreach ($createdRecords as $recordDto) {
            $this->yandexDnsApi->delete(
                $domainDto->domain, $domainDto->yandexToken, $recordDto->subdomain, DnsRecordTypesEnum::TXT
            );
        }
    }

    private function initConfigs(): void
    {
        $cliArgv = $this->cliArgv;
        $allowedParams = ['quiet'];
        $allowedUnnamedCount = 0;
        CliParametersRegistry::init($cliArgv, $allowedParams, $allowedUnnamedCount);

        $configAbsolutePath = $this->configAbsolutePath;
        DomainsParametersRegistry::init($configAbsolutePath);

        $this->isQuiet = CliParametersRegistry::get('quiet');

        DebugLib::init(
            $this->logAbsolutePath,
            $this->isQuiet ? DebugLib::MODE_LOG_ONLY : DebugLib::MODE_WITH_OUTPUT
        );

        Trans::instance()->addPhrases(__DIR__ . '/app-localization/ru.php');
    }
}