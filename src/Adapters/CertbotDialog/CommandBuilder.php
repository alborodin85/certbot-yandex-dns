<?php

namespace It5\Adapters\CertbotDialog;

use It5\ParametersParser\DomainParametersDto;

class CommandBuilder
{
    public function buildCommand(DomainParametersDto $parameters, string $commandPattern): string
    {
        $domains = '-d ' . implode(' -d ', $parameters->subDomains);
        $isDryRun = $parameters->isDryRun ? '--dry-run' : '';
        $isForce = $parameters->isForceRenewal ? '--force-renewal' : '';
        $isSudoMode = $parameters->isSudoMode ? 'sudo ' : '';
        $result = sprintf($commandPattern, $isSudoMode, $parameters->adminEmail, $domains, $isDryRun, $isForce);

        return $result;
    }
}