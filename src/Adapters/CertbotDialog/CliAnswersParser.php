<?php

namespace It5\Adapters\CertbotDialog;

use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;

class CliAnswersParser
{
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
        $pattern = "/with the following value:\s*(\S{35,50})+\s+.*/miu";

        $matches = [];
        $result = preg_match_all($pattern, $stringFromCertbotCli, $matches, PREG_PATTERN_ORDER);
        if (!$result) {
            throw new CertbotDialogError('Из диалога с CertBot не удалось извлечь значение dns-параметра!');
        }

        DebugLib::dump($matches);

        $dnsParamValue = '';
        foreach ($matches[1] as $foundedRow) {
            $dnsParamValue = $foundedRow;
            break;
        }
        return $dnsParamValue;
    }

    public function isCertbotResultOk(string $stringFromCertbotCli): bool
    {
        $errorIndicator = 'The following errors were reported by the server:';
        $stringFromCertbotCli = preg_replace('/\s+/', " ", $stringFromCertbotCli);
        $result = !str_contains($stringFromCertbotCli, $errorIndicator);

        return $result;
    }

    public function retrieveNewCertPath(string $stringFromCertbotCli): array
    {
        $pattern = '/Congratulations! Your certificate and chain have been saved at:\s+(.*?)\s+Your key file has been saved at:\s+(.*?)\s+Your cert will expire on\s*(.*?)\..*/mui';
        $matches = [];
        preg_match_all($pattern, $stringFromCertbotCli, $matches, PREG_PATTERN_ORDER);

        $certPath = trim($matches[1][0] ?? '');
        $privKeyPath = trim($matches[2][0] ?? '');
        $deadline = trim($matches[3][0] ?? '');
        if (!($certPath && $privKeyPath && $deadline)) {
            throw new CertbotDialogError('Из диалога с CertBot не удалось извлечь пути к сохраненным сертификатам!');
        }

        return [$certPath, $privKeyPath, $deadline];
    }
}
