<?php

namespace It5\TestsQuick\CheckCertNeedUpdate;

use It5\CheckCertNeedUpdate\CertDomainsChecker;
use It5\CheckCertNeedUpdate\CheckCertNeedUpdateError;
use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class CertDomainsCheckerTest extends TestCase
{
    private CertDomainsChecker $checker;
    private string $mainCertPath;

    protected function setUp(): void
    {
        DebugLib::init();
        $this->checker = new CertDomainsChecker();
        $this->mainCertPath = __DIR__ . '/s-tsk.ru_wild-2029-12-02.pem';
    }

    public function testEqual()
    {
        $subDomains = [
            0 => 's-tsk.ru',
            1 => '*.s-tsk.ru',
        ];
        $result = $this->checker->isDomainsChanged($this->mainCertPath, $subDomains, true);

        $this->assertFalse($result);
    }

    public function testDeleted() {
        $subDomains = [
            0 => 's-tsk.ru',
        ];
        $result = $this->checker->isDomainsChanged($this->mainCertPath, $subDomains, true);

        $this->assertTrue($result);
    }

    public function testAdded()
    {
        $subDomains = [
            0 => 's-tsk.ru',
            1 => '*.s-tsk.ru',
            2 => '*.ady.s-tsk.ru',
        ];
        $result = $this->checker->isDomainsChanged($this->mainCertPath, $subDomains, true);

        $this->assertTrue($result);
    }

    public function testError()
    {
        $certPath = __DIR__ . '/notCert.txt';
        $this->expectException(CheckCertNeedUpdateError::class);
        $this->checker->isDomainsChanged($certPath, [], true);
    }

    public function testCertAbsent()
    {
        $certPath = __DIR__ . '/absent-file.txt';
        $result = $this->checker->isDomainsChanged($certPath, [], true);
        $this->assertTrue($result);
    }
}
