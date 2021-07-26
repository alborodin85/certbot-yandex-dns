<?php

namespace It5\TestsQuick\SystemDnsShell;

use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\CliCommandExecutor;
use It5\SystemDnsShell\SystemDnsShellError;
use PHPUnit\Framework\TestCase;

class CliCommandExecutorTest extends TestCase
{
    public function setUp(): void
    {
        DebugLib::init();
    }

    public function testCorrectResult()
    {
        $executor = new CliCommandExecutor();
        $command = 'ls -a ';
        $args = __DIR__ . '/executor-test-folder';
        $correct = '. .. executor-test-file.txt';
        $result = $executor->getCommandResultString($command, $args);

        $this->assertEquals($correct, $result);
    }

    public function testEmptyCommand()
    {
        $executor = new CliCommandExecutor();
        $command = '';
        $args = '';

        $this->expectError();
        $executor->getCommandResultString($command, $args);

        $this->assertTrue(true);
    }

    public function testErrorCommand()
    {
        $executor = new CliCommandExecutor();
        $command = 'ls /not_exists_folder';
        $args = '';

        $this->expectError();
        $executor->getCommandResultString($command, $args);

        $this->assertTrue(true);
    }
}
