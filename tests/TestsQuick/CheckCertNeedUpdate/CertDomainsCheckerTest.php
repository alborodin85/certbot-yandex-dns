<?php

namespace It5\TestsQuick\CheckCertNeedUpdate;

use It5\CheckCertNeedUpdate\CertDomainsChecker;
use It5\CheckCertNeedUpdate\CheckCertNeedUpdateError;
use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;
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
        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($this->mainCertPath, $subDomains, true);

        $this->assertFalse($isDomainsChanged);
    }

    public function testDeleted()
    {
        $subDomains = [
            0 => 's-tsk.ru',
        ];
        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($this->mainCertPath, $subDomains, true);

        $this->assertTrue($isDomainsChanged);
    }

    public function testAdded()
    {
        $subDomains = [
            0 => 's-tsk.ru',
            1 => '*.s-tsk.ru',
            2 => '*.ady.s-tsk.ru',
        ];
        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($this->mainCertPath, $subDomains, true);

        $this->assertTrue($isDomainsChanged);
    }

    public function testError()
    {
        $certPath = __DIR__ . '/notCert.txt';
        $this->expectException(CheckCertNeedUpdateError::class);
        $this->checker->getSubdomainsChangesCounts($certPath, [], true);
    }

    public function testCertAbsent()
    {
        $certPath = __DIR__ . '/absent-file.txt';
        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($certPath, [], true);
        $this->assertTrue($isDomainsChanged);

        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($certPath, [], false);
        $this->assertTrue($isDomainsChanged);
    }

    public function testNotSudoFile()
    {
        $certPath = '/etc/letsencrypt/live/it5.su/fullchain2.pem';
        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($certPath, [], false);
        $this->assertTrue($isDomainsChanged);
    }

    public function testNotSudo()
    {
        $this->expectExceptionMessage(Trans::T('errors.define_domains_in_cert_error'));
        $certPath = '/etc/letsencrypt/live/it5.su/fullchain.pem';
        $this->checker->getSubdomainsChangesCounts($certPath, [], false);
    }

    public function testSudoFile()
    {
        $certPath = '/etc/letsencrypt/live/it5.su/fullchain2.pem';
        [$isDomainsChanged] =
            $this->checker->getSubdomainsChangesCounts($certPath, [], false);
        $this->assertTrue($isDomainsChanged);
    }

    public function testInvalidFile()
    {
        $this->expectExceptionMessage(Trans::T('errors.define_domains_in_cert_error'));
        $certPath = __DIR__ . '/notCert.txt';
        $this->checker->getSubdomainsChangesCounts($certPath, [], false);
    }
}
