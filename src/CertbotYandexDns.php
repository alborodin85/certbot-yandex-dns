<?php

namespace It5;

use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\CertUpdateSteps\CheckCertDeadline\CertDeadlineChecker;
use It5\ParametersParser\CliParametersRegistry;
use It5\ParametersParser\DomainParametersDto;
use It5\ParametersParser\DomainsParametersRegistry;
use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use It5\Adapters\YandexApi\YandexDnsApi;

class CertbotYandexDns
{
    private static self $selfInstance;

    private bool $isQuiet;
    private CertDeadlineChecker $certDeadlineChecker;
    private YandexDnsApi $yandexDnsApi;
    private CertbotDialog $certbotDialog;

    public static function singleton(
        array $cliArgv,
        string $configAbsolutePath,
        string $logAbsolutePath,
        int $delayMicroseconds,
    ): self {
        if (empty(self::$selfInstance)) {
            self::$selfInstance = new self($cliArgv, $configAbsolutePath, $logAbsolutePath, $delayMicroseconds);
        }

        return self::$selfInstance;
    }

    private function __construct(
        private $cliArgv,
        private string $configAbsolutePath,
        private string $logAbsolutePath,
        private int $delayMicroseconds,
    ) {
        $this->initConfigs();
        $this->certDeadlineChecker = new CertDeadlineChecker();
        $this->yandexDnsApi = new YandexDnsApi($this->delayMicroseconds);
        $this->certbotDialog = new CertbotDialog();
    }

    public function replaceDeadlineChecker(CertDeadlineChecker $checker): void
    {
        $this->certDeadlineChecker = $checker;
    }

    public function replaceYandexDnsApi(YandexDnsApi $yandexDnsApi)
    {
        $this->yandexDnsApi = $yandexDnsApi;
    }

    public function replaceCertbotDialog(CertbotDialog $certbotDialog)
    {
        $this->certbotDialog = $certbotDialog;
    }

    public function renewCerts(): bool
    {
        // DebugLib::singleton()->dump($this->logAbsolutePath);
        // Прочитать конфиг
        // DebugLib::singleton()->dump($this->configAbsolutePath);
        $domains = DomainsParametersRegistry::getCollection();
        foreach ($domains as $domainDto) {
            $this->updateCertForOneDomain($domainDto);
        }

        return true;
    }

    private function updateCertForOneDomain(DomainParametersDto $domainDto): void
    {
        // Проверить срок сертификата
        if (!$this->certDeadlineChecker->check($domainDto->certPath, $domainDto->criticalRemainingDays)) {
            return;
        }
        // Проверить, нет ли в зоне параметров; если есть - удалить
        $this->yandexDnsApi->delete(
            $domainDto->domain, $domainDto->yandexToken, $domainDto->dnsParameterName, DnsRecordTypesEnum::TXT
        );
        // Начать диалог с CertBot
        $dialogDto = $this->certbotDialog->openDialog($domainDto, DebugLib::singleton()->logFile);
        // Получить из диалога требуемые DNS-параметры
        $arDnsRecords = $this->certbotDialog->getRequiredDnsRecords($dialogDto, $domainDto);
        // Создать в зоне требуемые параметры
        foreach ($arDnsRecords as $arRecord) {
            foreach ($arRecord as $subDomain => $recordText) {
                $this->yandexDnsApi->create(
                    $domainDto->domain, $domainDto->yandexToken, DnsRecordTypesEnum::TXT, $subDomain, $recordText
                );
            }
        }
        // Дождаться, пока зона распространится

        // Дать последний Enter CertBot

        // Закончить диалог с CertBot
        $this->certbotDialog->closeDialog($dialogDto);

        // Вывести результат
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
    }
}