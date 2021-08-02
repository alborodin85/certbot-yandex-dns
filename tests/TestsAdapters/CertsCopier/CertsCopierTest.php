<?php

namespace It5\TestsAdapters\CertsCopier;

use It5\Adapters\CertsCopier\CertsCopier;
use It5\DebugLibs\DebugLib;
use Monolog\Test\TestCase;

class CertsCopierTest extends TestCase
{
    private $normalFromCert = __DIR__ . '/from/normalFromCert';
    private $errorFromCert = __DIR__ . '/from/errorFromCert';
    private $normalToCert = __DIR__ . '/to/normalToCert';
    private $certPerms = "0770";

    private $normalFromPrivKey = __DIR__ . '/from/normalFromPrivKey';
    private $errorFromPrivKey = __DIR__ . '/from/errorFromPrivKey';
    private $normalToPrivKey = __DIR__ . '/to/normalToPrivKey';
    private $privKeyPerms = "0750";

    private CertsCopier $certsCopier;
    
    public function setUp(): void
    {
        DebugLib::init();
        $this->certsCopier = new CertsCopier();
    }
    public function tearDown(): void
    {
        if (is_file($this->normalToCert)) {
            unlink($this->normalToCert);
        }
        if (is_file($this->normalToPrivKey)) {
            unlink($this->normalToPrivKey);
        }
        if (is_dir(dirname($this->normalToCert))) {
            rmdir(dirname($this->normalToCert));
        }
        if (is_dir(dirname($this->normalToPrivKey))) {
            rmdir(dirname($this->normalToPrivKey));
        }
    }

    public function testError()
    {
        $result = $this->certsCopier->copyCertAndKey(
            $this->errorFromCert, $this->normalToCert, $this->certPerms,
            $this->errorFromPrivKey, $this->normalToPrivKey, $this->privKeyPerms,
        );

        $this->assertFalse($result);
    }

    public function testNormalBoth()
    {
        $result = $this->certsCopier->copyCertAndKey(
            $this->normalFromCert, $this->normalToCert, $this->certPerms,
            $this->normalFromPrivKey, $this->normalToPrivKey, $this->privKeyPerms,
        );

        $this->assertTrue($result);

        $this->assertTrue(is_file($this->normalToCert));

        $dirPath = dirname($this->normalToCert);
        $factPerms = fileperms($dirPath);
        $factPerms = base_convert($factPerms, 10, 8);
        $this->assertEquals('4' . $this->certPerms, $factPerms);

        $factPerms = fileperms($this->normalToCert);
        $factPerms = base_convert($factPerms, 10, 8);
        $this->assertEquals('10' . $this->certPerms, $factPerms);

        $this->assertTrue(is_file($this->normalToPrivKey));

        $factPerms = fileperms($this->normalToPrivKey);
        $factPerms = base_convert($factPerms, 10, 8);
        $this->assertEquals('10' . $this->privKeyPerms, $factPerms);


    }
    
    public function testNormalCert()
    {
        $result = $this->certsCopier->copyCertAndKey(
            $this->normalFromCert, $this->normalToCert, $this->certPerms,
            $this->errorFromPrivKey, $this->normalToPrivKey, $this->privKeyPerms,
        );

        $this->assertFalse($result);
    }

    public function testNormalPrivKey()
    {
        $result = $this->certsCopier->copyCertAndKey(
            $this->errorFromCert, $this->normalToCert, $this->certPerms,
            $this->normalFromPrivKey, $this->normalToPrivKey, $this->privKeyPerms,
        );

        $this->assertFalse($result);
    }
    
}
