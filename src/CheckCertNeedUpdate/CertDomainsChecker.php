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

    public function checkDomainsChanged()
    {

    }

    private function getCertDomainsString(string $certPath): string
    {
        $commandResult = `openssl x509 -enddate -noout -in {$certPath}`;
        $commandResult = str_replace("\n", '', $commandResult);
        $commandResult = trim($commandResult);
        $commandResult = str_replace('notAfter=', '', $commandResult);
        $testTime = strtotime($commandResult);
        if (!$testTime) {
            DebugLib::dump(Trans::T('errors.openssl_error'));
        }

        return $commandResult;
    }
}