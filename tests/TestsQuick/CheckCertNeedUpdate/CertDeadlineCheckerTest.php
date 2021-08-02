<?php

namespace It5\TestsQuick\CheckCertNeedUpdate;

use It5\CheckCertNeedUpdate\CertDeadlineChecker;
use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;
use PHPUnit\Framework\TestCase;

class CertDeadlineCheckerTest extends TestCase
{
    private CertDeadlineChecker $checker;
    private int $criticalRemainingDays;

    public function setUp(): void
    {
        DebugLib::init();
        $this->checker = new CertDeadlineChecker();
        $this->criticalRemainingDays = 7;
    }
    public function testCheck()
    {
        $certPath = __DIR__ . '/s-tsk.ru_wild-2029-12-02.pem';
        $result = $this->checker->isPeriodCritical($certPath, $this->criticalRemainingDays, true);
        $this->assertFalse($result);

        $certPath = __DIR__ . '/fullchain1-2020-06-12.pem';
        $result = $this->checker->isPeriodCritical($certPath, $this->criticalRemainingDays, true);
        $this->assertTrue($result);
    }

    public function testNotSudoFile()
    {
        $certPath = '/etc/letsencrypt/live/it5.su/fullchain2.pem';
        $result = $this->checker->isPeriodCritical($certPath, $this->criticalRemainingDays, false);
        $this->assertTrue($result);
    }

    public function testSudoFile()
    {
        $certPath = '/etc/letsencrypt/live/it5.su/fullchain2.pem';
        $result = $this->checker->isPeriodCritical($certPath, $this->criticalRemainingDays, true);
        $this->assertTrue($result);
    }

    public function testInvalidFile()
    {
        $this->expectExceptionMessage(Trans::T('errors.openssl_error'));
        $certPath = __DIR__ . '/notCert.txt';
        $this->checker->isPeriodCritical($certPath, $this->criticalRemainingDays, true);
    }

    public function testNotSudo()
    {
        $this->expectExceptionMessage(Trans::T('errors.openssl_error'));
        $certPath = '/etc/letsencrypt/live/it5.su/fullchain.pem';
        $this->checker->isPeriodCritical($certPath, $this->criticalRemainingDays, false);
    }
}
