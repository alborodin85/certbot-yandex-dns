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

    public function isDomainsChanged(string $certPath, array $subDomains): bool
    {
        try {
            $fileType = filetype($certPath);
            if (!$fileType) {
                return true;
            }
        } catch (\Throwable) {
            return true;
        }

        $existDomains = $this->getCertDomains($certPath);

        // Проверяем, что в конфиге не появились новые домены
        foreach ($subDomains as $newDomain) {
            if (!in_array($newDomain, $existDomains)) {
                return true;
            }
        }

        // Проверяем, что из конфига домены не удаляли
        foreach ($existDomains as $existDomain) {
            if (!in_array($existDomain, $subDomains)) {
                return true;
            }
        }

        return false;
    }

    private function getCertDomains(string $certPath): array
    {
        $commandResult = `sudo openssl x509 -noout -in {$certPath} -ext subjectAltName`;

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