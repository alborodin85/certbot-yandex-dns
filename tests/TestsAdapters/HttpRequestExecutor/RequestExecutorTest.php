<?php

namespace It5\TestsAdapters\HttpRequestExecutor;

use It5\Adapters\HttpRequestExecutor\HttpRequestExecutorError;
use It5\Adapters\HttpRequestExecutor\RequestExecutor;
use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class RequestExecutorTest extends TestCase
{
    const TEST_HOST = 'https://test-host.it5.su';

    private RequestExecutor $executor;
    private string $testParameterName = 'test-parameter-name';
    private string $testParameterValue = 'test-parameter-value';
    private string $testHeaderName = 'test-header-name';
    private string $testHeaderValue = 'test-header-value: 1';

    public function setUp(): void
    {
        DebugLib::init();
        $this->executor = new RequestExecutor();
    }

    public function testGetSimpleString()
    {
        $url = self::TEST_HOST . '/CurlShell/RequestExecutorTest/string-test.php';

        $parameters = [$this->testParameterName => $this->testParameterValue];
        $headers = [$this->testHeaderName => $this->testHeaderValue];
        $method = RequestExecutor::METHOD_GET;
        $result = $this->executor->makeUrlRequest($url, $method, $parameters, $headers);

        file_put_contents(__DIR__ . '/simple-string.txt', $result);

        self::assertTrue(true);
    }

    public function testMakeUrlRequest()
    {
        $url = self::TEST_HOST . '/CurlShell/RequestExecutorTest/';
        $parameters = [$this->testParameterName => $this->testParameterValue];
        $headers = [$this->testHeaderName => $this->testHeaderValue];
        $method = RequestExecutor::METHOD_GET;
        $result = $this->executor->makeUrlRequest($url, $method, $parameters, $headers);

        $this->assertStringContainsString($this->testParameterName, $result);
        $this->assertStringContainsString($this->testParameterValue, $result);
        $this->assertStringContainsString($this->testHeaderName, $result);
        $this->assertStringContainsString($this->testHeaderValue, $result);
        $this->assertStringContainsString('"REQUEST_METHOD": "GET"', $result);

        $method = RequestExecutor::METHOD_DELETE;

        $result = $this->executor->makeUrlRequest($url, $method, $parameters, $headers);

        $this->assertStringContainsString($this->testParameterName, $result);
        $this->assertStringContainsString($this->testParameterValue, $result);
        $this->assertStringContainsString($this->testHeaderName, $result);
        $this->assertStringContainsString($this->testHeaderValue, $result);
        $this->assertStringContainsString('"REQUEST_METHOD": "DELETE"', $result);

        $method = RequestExecutor::METHOD_PATCH;

        $result = $this->executor->makeUrlRequest($url, $method, $parameters, $headers);

        $this->assertStringContainsString($this->testParameterName, $result);
        $this->assertStringContainsString($this->testParameterValue, $result);
        $this->assertStringContainsString($this->testHeaderName, $result);
        $this->assertStringContainsString($this->testHeaderValue, $result);
        $this->assertStringContainsString('"REQUEST_METHOD": "PATCH"', $result);
    }

    public function testMakeDataRequest()
    {
        $url = self::TEST_HOST . '/CurlShell/RequestExecutorTest/';
        $parameters = [$this->testParameterName => $this->testParameterValue];
        $headers = [$this->testHeaderName => $this->testHeaderValue];
        $method = RequestExecutor::METHOD_POST;
        $constraint = $this->logicalAnd(
            $this->stringContains($this->testParameterName),
            $this->stringContains($this->testParameterValue),
            $this->stringContains($this->testHeaderName),
            $this->stringContains($this->testHeaderValue),
            $this->stringContains('"REQUEST_METHOD": "POST"'),
        );

        $result = $this->executor->makeDataRequest($url, $method, $parameters, $headers);
        $this->assertThat($result, $constraint);

        $result = $this->executor->sendFile(
            $url, $method, $parameters, $headers, '', __DIR__ . '/test-file.txt', ''
        );
        $this->assertThat($result, $constraint);
        $this->assertStringContainsString('test-file.txt', $result);
        $this->assertStringContainsString('form-data; boundary', $result);

        $this->expectException(HttpRequestExecutorError::class);
        $this->executor->sendFile(
            $url, $method, $parameters, $headers, '', 'text-file.txt', ''
        );

    }
}
