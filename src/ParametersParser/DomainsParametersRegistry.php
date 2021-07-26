<?php

namespace It5\ParametersParser;

use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;

class DomainsParametersRegistry
{

    private DomainsParametersCollection $parameters;

    private function __construct(
        private string $configAbsolutePath,
    ) {
        Trans::instance()->addPhrases(__DIR__ . '/localization/ru.php');
        $this->parameters = $this->parseDomainSettingsFile();
    }

    private static self | null $instance;

    public static function init(string $configAbsolutePath): self
    {
        self::$instance = new self($configAbsolutePath);

        return self::$instance;
    }

    public static function singleton(): self
    {
        if (empty(self::$instance)) {
            throw new DomainsParametersError(Trans::T('errors.domain_registry_not_inited'));
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function getCollection(): DomainsParametersCollection
    {
        return self::singleton()->parameters;
    }

    public static function getParametersForDomain(string $domain): DomainParametersDto
    {
        $result = array_filter(
            self::singleton()->parameters->toArray(),
            fn(DomainParametersDto $parametersDto) => $parametersDto->domain == $domain,
        );

        if (!count($result)) {
            throw new DomainsParametersError(Trans::T('errors.domain_absent', $domain));
        }
        if (count($result) > 1) {
            throw new DomainsParametersError(Trans::T('errors.domain_redundant_record', $domain));
        }

        return reset($result);
    }

    public function parseDomainSettingsFile(): DomainsParametersCollection
    {
        $strSettings = file_get_contents($this->configAbsolutePath);
        $arSettings = json_decode($strSettings, true);

        $collection = new DomainsParametersCollection();

        $id = 1;
        $domain = '';
        $subDomains = [];
        $adminEmail = '';
        $yandexToken = '';
        $isDryRun = false;
        $isForceRenewal = true;
        $isSudoMode = false;
        $dnsParameterName = '_acme-challenge';
        $criticalRemainingDays = 7;
        $certPath = '';
        foreach ($arSettings as $arDomainItem) {
            $domain = $arDomainItem['domain'] ?? $domain;
            $this->checkParam($domain, 'domain', $domain);
            $subDomains = $arDomainItem['subDomains'] ?? $subDomains;
            $this->checkParam($domain, 'subDomains', count($subDomains));
            $adminEmail = $arDomainItem['adminEmail'] ?? $adminEmail;
            $this->checkParam($domain, 'adminEmail', $adminEmail);
            $yandexToken = $arDomainItem['yandexToken'] ?? $yandexToken;
            $this->checkParam($domain, 'yandexToken', $yandexToken);
            $certPath = $arDomainItem['certPath'] ?? $certPath;
            $this->checkParam($domain, 'certPath', $certPath);

            $dnsParameterName = $arDomainItem['dnsParameterName'] ?? $dnsParameterName;
            $criticalRemainingDays = $arDomainItem['criticalRemainingDays'] ?? $criticalRemainingDays;
            $isDryRun = $arDomainItem['isDryRun'] ?? $isDryRun;
            $isForceRenewal = $arDomainItem['isForceRenewal'] ?? $isForceRenewal;
            $isSudoMode = $arDomainItem['isSudoMode'] ?? $isSudoMode;

            $domainItemDto = new DomainParametersDto(
                id: $id++,
                domain: $domain,
                subDomains: $subDomains,
                adminEmail: $adminEmail,
                yandexToken: $yandexToken,
                dnsParameterName: $dnsParameterName,
                criticalRemainingDays: $criticalRemainingDays,
                certPath: $certPath,
                isDryRun: $isDryRun,
                isForceRenewal: $isForceRenewal,
                isSudoMode: $isSudoMode,
            );

            $collection[] = $domainItemDto;
        }

        return $collection;
    }

    private function checkParam(string $domain, string $paramName, string $paramValue): bool
    {
        if (!$paramValue) {
            throw new DomainsParametersError(Trans::T('errors.domain_empty_param', $domain, $paramName));
        }

        return true;
    }
}