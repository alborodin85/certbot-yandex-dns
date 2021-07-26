<?php

namespace It5\CheckCertNeedUpdate;

use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;

class CertDeadlineChecker
{
    public function __construct()
    {
        Trans::instance()->addPhrases(__DIR__ . '/localization/ru.php');
    }

    public function checkDeadline(string $certPath, int $criticalRemainingDays): bool
    {
        $result = true;

        try {
            $fileType = filetype($certPath);
            if (!$fileType) {
                return true;
            }
        } catch (\Throwable) {
            return true;
        }

        $certDeadline = $this->getCertDeadlineString($certPath);
        if (!$this->checkNeedUpdate($certDeadline, $criticalRemainingDays)) {
            $result = false;
        }

        return $result;
    }

    private function getCertDeadlineString(string $certPath): string
    {
        $commandResult = `sudo openssl x509 -enddate -noout -in {$certPath}`;
        $commandResult = str_replace("\n", '', $commandResult);
        $commandResult = trim($commandResult);
        $commandResult = str_replace('notAfter=', '', $commandResult);
        $testTime = strtotime($commandResult);
        if (!$testTime) {
            DebugLib::dump(Trans::T('errors.openssl_error'));
        }

        return $commandResult;
    }

    private function checkNeedUpdate(string $certDeadline, int $criticalRemainingDays): bool
    {
        $deadLineTimestamp = strtotime($certDeadline);
        $currentTimestamp = time();

        if ($deadLineTimestamp) {
            $needUpdate = ($deadLineTimestamp - $currentTimestamp) < $criticalRemainingDays * 24 * 3600;
        } else {
            $needUpdate = false;
        }

        return $needUpdate;
    }
}