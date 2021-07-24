<?php

namespace It5\TestsQuick\CertUpdateSteps\CheckCertDeadline;

use It5\CheckCertNeedUpdate\CertDeadlineChecker;
use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class CertDeadlineCheckerTest extends TestCase
{
    public function testCheck()
    {
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
        $checker = new CertDeadlineChecker();
        $certPath = __DIR__ . '/s-tsk.ru_wild-2029-12-02.pem';
        $criticalRemainingDays = 7;
        $result = $checker->checkDeadline($certPath, $criticalRemainingDays);
        $this->assertFalse($result);

        $certPath = __DIR__ . '/notCert.txt';
        $result = $checker->checkDeadline($certPath, $criticalRemainingDays);
        $this->assertFalse($result);

        $certPath = __DIR__ . '/fullchain1-2020-06-12.pem';
        $result = $checker->checkDeadline($certPath, $criticalRemainingDays);
        $this->assertTrue($result);
    }
}
