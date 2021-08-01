<?php

namespace ParametersParser;

use It5\DebugLibs\DebugLib;
use It5\ParametersParser\CliParametersError;
use It5\ParametersParser\CliParametersRegistry;
use PHPUnit\Framework\TestCase;

class CliParametersRegistryTest extends TestCase
{
    private array $cliArgv;
    private array $allowedParams;
    private int $allowedUnnamedCount;

    public function setUp(): void
    {
        DebugLib::init();
        $this->cliArgv = [
            __FILE__,
            '--quiet',
            '/var/lib/mysql',
            '--config=/gfd/dfd'
        ];
        $this->allowedParams = [
            'quiet', 'config'
        ];
        $this->allowedUnnamedCount = 1;
        CliParametersRegistry::init(
            $this->cliArgv, $this->allowedParams, $this->allowedUnnamedCount
        );
    }
    public function tearDown(): void
    {
        CliParametersRegistry::reset();
    }

    public function testGet()
    {
        $this->assertTrue(!!CliParametersRegistry::get('quiet'));
        $this->assertFalse(!!CliParametersRegistry::get('loud'));
        $this->assertEquals('/gfd/dfd', CliParametersRegistry::get('config'));
        $this->assertEquals('/var/lib/mysql', CliParametersRegistry::get('param1'));
    }

    public function testRedundantParameter()
    {
        $this->expectException(CliParametersError::class);
        CliParametersRegistry::init(
            $this->cliArgv, ['quiet'], $this->allowedUnnamedCount
        );
    }

    public function testCountParametersError()
    {
        $this->expectException(CliParametersError::class);
        CliParametersRegistry::init($this->cliArgv, ['quiet'], 0);
    }

    public function testInit()
    {
        $constraint = $this->logicalAnd(
            $this->isType('array'),
            $this->equalTo($this->cliArgv)
        );
        self::assertThat(CliParametersRegistry::singleton()->getInstanceProperty('cliArgv'), $constraint);
    }

    public function testSingleton()
    {
        $this->assertInstanceOf(CliParametersRegistry::class, CliParametersRegistry::singleton());
    }

    public function testSingletonError()
    {
        CliParametersRegistry::reset();
        $this->expectException(CliParametersError::class);
        CliParametersRegistry::singleton();
    }
}
