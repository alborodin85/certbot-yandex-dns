<?php

namespace It5;

use It5\CertUpdateSteps\CheckCertDeadline\CertDeadlineChecker;
use It5\ParametersParser\CliParametersRegistry;
use It5\ParametersParser\DomainParametersDto;
use It5\ParametersParser\DomainsParametersRegistry;
use It5\DebugLibs\DebugLib;

class CertbotYandexDns
{
    private static self $selfInstance;

    private bool $isQuiet;
    private CertDeadlineChecker $certDeadlineChecker;

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
    }

    public function replaceDeadlineChecker(CertDeadlineChecker $checker): void
    {
        $this->certDeadlineChecker = $checker;
    }

    public function renewCerts(): bool
    {
        DebugLib::singleton()->dump($this->logAbsolutePath);
        // Прочитать конфиг
        DebugLib::singleton()->dump($this->configAbsolutePath);
        $domains = DomainsParametersRegistry::getCollection();
        DebugLib::dump($domains);
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
        // Проверить, нет ли в зоне параметра; если есть - удалить
        // Запустить диалог
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