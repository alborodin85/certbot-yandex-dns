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

    public function isPeriodCritical(string $certPath, int $criticalRemainingDays, bool $isSudoMode): bool
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

        $certDeadline = $this->getCertDeadlineString($certPath, $isSudoMode);
        if (!$this->checkNeedUpdate($certDeadline, $criticalRemainingDays)) {
            $result = false;
        }

        return $result;
    }

    private function getCertDeadlineString(string $certPath, bool $isSudoMode): string
    {
//        $commandPattern = "sudo openssl x509 -enddate -noout -in {$certPath}";
        $commandPattern = "%s openssl x509 -enddate -noout -in %s";
        $isSudoMode = $isSudoMode ? 'sudo ' : '';
        $command = sprintf($commandPattern, $isSudoMode, $certPath);
        $commandResult = `{$command}`;

        $commandResult = str_replace("\n", '', $commandResult);
        $commandResult = trim($commandResult);
        $commandResult = str_replace('notAfter=', '', $commandResult);
        $testTime = strtotime($commandResult);
        if (!$testTime) {
            throw new CheckCertNeedUpdateError(Trans::T('errors.openssl_error'));
        }

        return $commandResult;
    }

    private function checkNeedUpdate(string $certDeadline, int $criticalRemainingDays): bool
    {
        $deadLineTimestamp = strtotime($certDeadline);
        $currentTimestamp = time();

        $needUpdate = ($deadLineTimestamp - $currentTimestamp) < $criticalRemainingDays * 24 * 3600;

        return $needUpdate;
    }
}