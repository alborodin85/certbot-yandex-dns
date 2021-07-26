<?php

namespace It5\Adapters\CertbotDialog;

use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;

class CommandBuilder
{
    const COMMAND_PATTERN = 'sudo certbot certonly --manual-public-ip-logging-ok --agree-tos --email %s --renew-by-default %s --manual --preferred-challenges dns-01 --server https://acme-v02.api.letsencrypt.org/directory %s %s';

    public function buildCommand(DomainParametersDto $parameters): string
    {
        $domains = '-d ' . implode(' -d ', $parameters->subDomains);
        $isDryRun = $parameters->dryRun ? '--dry-run' : '';
        $isForce = '--force-renewal';
        $result = sprintf(self::COMMAND_PATTERN, $parameters->adminEmail, $domains, $isDryRun);

        return $result;
    }

    public function retrieveDnsParameterName(string $stringFromCertbotCli, DomainParametersDto $parameters): string
    {
        $pattern = "/.*?({$parameters->dnsParameterName}(\.[\w\-]+)+).*?/miu";

        $matches = [];
        $result = preg_match($pattern, $stringFromCertbotCli, $matches);
        if (!$result) {
            throw new CertbotDialogError('Из диалога с CertBot не удалось извлечь имя dns-параметра!');
        }

        $fullName = $matches[1];
        $arFullName = explode('.', $fullName);
        $dnsParamName = '';
        for ($i = 0; $i < (count($arFullName) - 2); $i++) {
            $dnsParamName .= $arFullName[$i] . '.';
        }
        $dnsParamName = mb_substr($dnsParamName, 0, mb_strlen($dnsParamName) - 1);

        return $dnsParamName;
    }

    public function retrieveDnsParameterValue(string $stringFromCertbotCli, string $dnsParamName): string
    {
        $pattern = "/^\s*(\S{35,50})?\s+.*/miu";

        $matches = [];
        $result = preg_match_all($pattern, $stringFromCertbotCli, $matches, PREG_PATTERN_ORDER);
        if (!$result) {
            throw new CertbotDialogError('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        }

        $dnsParamValue = '';
        foreach ($matches[1] as $foundedRow) {
            if (str_contains($foundedRow, $dnsParamName)) {
                continue;
            }
            if (!$foundedRow) {
                continue;
            }
            $dnsParamValue = $foundedRow;
            break;
        }

        if (!$dnsParamValue) {
            throw new CertbotDialogError('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        }

        return $dnsParamValue;
    }
}