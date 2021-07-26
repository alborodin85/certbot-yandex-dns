<?php

namespace It5\ParametersParser;

class DomainParametersDto
{
    public function __construct(
        public int $id,
        public string $domain,
        public array $subDomains,
        public string $adminEmail,
        public string $yandexToken,
        public string $dnsParameterName,
        public int $criticalRemainingDays,
        public string $certPath,
        public bool $isDryRun,
        public bool $isForceRenewal,
        public bool $isSudoMode,
    ) {}
}