<?php

namespace DebugLibs;

use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class DebugLibTest extends TestCase
{
    private string $logFile;
    private string $mode;
    private string $message;

    public function setUp(): void
    {
        $this->message = 'test-test-test';
        $this->logFile = __DIR__ . '/test-app.log';
        $this->mode = DebugLib::MODE_WITH_OUTPUT;
        DebugLib::init($this->logFile, $this->mode);
    }

    public function tearDown(): void
    {
        DebugLib::reset();
    }

    public function testEmptyInstance()
    {
        DebugLib::reset();
        $this->expectExceptionMessage('DebugLib not initialized!');
        DebugLib::singleton();
    }

    public function testLdCorrect()
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
            DebugLib::init($this->logFile, $this->mode);
        }
        DebugLib::ld($this->message);
        $logContent = file_get_contents($this->logFile);
        $result = str_contains($logContent, $this->message);
        $this->assertTrue($result);
    }

    public function testDumpCorrect()
    {
        ob_start();
        $result = DebugLib::dump($this->message);
        ob_end_clean();
        $this->assertTrue($result);
    }

    public function testModeLogOnly()
    {
        DebugLib::reset();
        $mode = DebugLib::MODE_LOG_ONLY;
        DebugLib::init($this->logFile, $mode);
        $this->assertFalse(DebugLib::dump($this->message));
    }

    public function testQuiet()
    {
        DebugLib::reset();
        unlink($this->logFile);
        $mode = DebugLib::MODE_QUIET;
        DebugLib::init($this->logFile, $mode);
        DebugLib::ld($this->message);
        $logContent = file_get_contents($this->logFile);
        $result = str_contains($logContent, $this->message);
        $this->assertFalse($result);
    }

    public function testTwoParams()
    {
        ob_start();
        $result = DebugLib::dump('title', $this->message);
        ob_end_clean();
        $this->assertTrue($result);
    }

    public function testPrintAndLog()
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
            DebugLib::init($this->logFile, $this->mode);
        }

        ob_start();
        DebugLib::printAndLog($this->message);
        ob_end_clean();

        $logContent = file_get_contents($this->logFile);
        $result = str_contains($logContent, $this->message);
        $this->assertTrue($result);
    }

    public function testPrint()
    {
        ob_start();
        $result = DebugLib::print($this->message);
        ob_end_clean();
        $this->assertTrue($result);
    }

}
