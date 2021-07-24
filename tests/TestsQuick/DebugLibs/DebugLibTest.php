<?php

namespace DebugLibs;

use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class DebugLibTest extends TestCase
{
    private string $logFile;
    private string $message;

    public function setUp(): void
    {
        $this->message = 'test-test-test';
        $this->logFile = __DIR__ . '/test-app.log';
        $mode = DebugLib::MODE_WITH_OUTPUT;
        DebugLib::init($this->logFile, $mode);
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

    public function testClass()
    {
        if (is_file($this->logFile)) {
            unlink($this->logFile);
        }
        DebugLib::ld($this->message);
        $logContent = file_get_contents($this->logFile);
        $result = str_contains($logContent, $this->message);
        $this->assertTrue($result);

        ob_start();
        $result = DebugLib::dump($this->message);
        ob_end_clean();
        $this->assertTrue($result);

        DebugLib::reset();
        $mode = DebugLib::MODE_LOG_ONLY;
        DebugLib::init($this->logFile, $mode);
        $this->assertFalse(DebugLib::dump($this->message));

        DebugLib::reset();
        unlink($this->logFile);
        $mode = DebugLib::MODE_QUIET;
        DebugLib::init($this->logFile, $mode);
        DebugLib::ld($this->message);
        $this->expectError();
        $logContent = file_get_contents($this->logFile);
        $result = str_contains($logContent, $this->message);
        $this->assertFalse($result);
    }

    public function testLogFileNotDefined()
    {
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
        $this->expectExceptionMessage('DebugLib::logFile not defined!');
        DebugLib::ld($this->message);
    }

}
