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
        public string $certPath,
        public string $certPermissions,
        public string $privKeyPath,
        public string $privKeyPermissions,

        public string $dnsParameterName = '_acme-challenge',
        public int $criticalRemainingDays = 15,
        public bool $isDryRun = false,
        public bool $isForceRenewal = true,
        public bool $isSudoMode = false,
    ) {}
}
