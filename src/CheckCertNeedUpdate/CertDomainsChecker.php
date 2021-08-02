<?php

namespace It5\CheckCertNeedUpdate;

use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;

class CertDomainsChecker
{
    public function __construct()
    {
        Trans::instance()->addPhrases(__DIR__ . '/localization/ru.php');
    }

    public function getSubdomainsChangesCounts(string $certPath, array $subDomains, bool $isSudoMode): array
    {
        $isDomainsChanged = false;
        $countAdded = 0;
        $countDeleted = 0;

        try {
            $fileType = filetype($certPath);
            // TODO: Тестируется на реальном пути для сертификата Certbot
            if (!$fileType) {
                return [$isDomainsChanged, $countAdded, $countDeleted];
            }
        } catch (\Throwable) {
            $isDomainsChanged = true;
            return [$isDomainsChanged, $countAdded, $countDeleted];
        }

        $existDomains = $this->getCertDomains($certPath, $isSudoMode);

        // Проверяем, что в конфиге не появились новые домены
        foreach ($subDomains as $newDomain) {
            if (!in_array($newDomain, $existDomains)) {
                $isDomainsChanged = true;
                $countAdded++;
            }
        }

        // Проверяем, что из конфига домены не удаляли
        foreach ($existDomains as $existDomain) {
            if (!in_array($existDomain, $subDomains)) {
                $isDomainsChanged = true;
                $countDeleted++;
            }
        }

        return [$isDomainsChanged, $countAdded, $countDeleted];
    }

    private function getCertDomains(string $certPath, bool $isSudoMode): array
    {
        $commandPattern = "%s openssl x509 -noout -in %s -ext subjectAltName";
        $isSudoMode = $isSudoMode ? 'sudo ' : '';
        $command = sprintf($commandPattern, $isSudoMode, $certPath);
        $commandResult = `{$command}`;

        $matches = [];
        $parse_result = preg_match('/.*?(DNS:.*){1}.*/miu', $commandResult, $matches);
        if ($parse_result == 0) {
            throw new CheckCertNeedUpdateError(Trans::T('errors.define_domains_in_cert_error'));
        }

        $domainsStr = $matches[1];
        $domainsStr = preg_replace('/\s/', '', $domainsStr);
        $domainsStr = str_replace('DNS:', '', $domainsStr);
        $arDomains = explode(',', $domainsStr);

        return $arDomains;
    }
}
