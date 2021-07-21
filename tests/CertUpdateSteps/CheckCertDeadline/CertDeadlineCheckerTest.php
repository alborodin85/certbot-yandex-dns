<?php

namespace CertUpdateSteps\CheckCertDeadline;

use It5\CertUpdateSteps\CheckCertDeadline\CertDeadlineChecker;
use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class CertDeadlineCheckerTest extends TestCase
{
    public function testCheck()
    {
        ob_start();
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
        $checker = new CertDeadlineChecker();
        $certPath = __DIR__ . '/s-tsk.ru_wild-2029-12-02.pem';
        $criticalRemainingDays = 7;
        $result = $checker->check($certPath, $criticalRemainingDays);
        $this->assertFalse($result);

        $certPath = __DIR__ . '/notCert.txt';
        $result = $checker->check($certPath, $criticalRemainingDays);
        $this->assertFalse($result);

        $certPath = __DIR__ . '/fullchain1-2020-06-12.pem';
        $result = $checker->check($certPath, $criticalRemainingDays);
        $this->assertTrue($result);
        ob_end_clean();
    }
}
