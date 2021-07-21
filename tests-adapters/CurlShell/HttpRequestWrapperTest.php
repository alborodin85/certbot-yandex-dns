<?php

namespace CurlShell;

use It5\CurlShell\HttpRequestWrapper;
use It5\CurlShell\RequestExecutor;
use It5\DebugLibs\DebugLib;
use PHPUnit\Framework\TestCase;

class HttpRequestWrapperTest extends TestCase
{
    const TEST_HOST = 'https://test-host.it5.su';

    private string $testParameterName = 'test-parameter-name';
    private string $testParameterValue = 'test-parameter-value';
    private string $testHeaderName = 'test-header-name';
    private string $testHeaderValue = 'test-header-value';

    public function setUp(): void
    {
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
    }

    public function testDump()
    {
        $url = self::TEST_HOST . '/CurlShell/RequestExecutorTest/';
        $constraint = $this->identicalTo([$this->testParameterValue, $this->testHeaderValue]);

        $result = $this->getTestDumpResult($url, RequestExecutor::METHOD_GET, [], []);
        $this->assertThat($result, $constraint);

        $result = $this->getTestDumpResult($url, RequestExecutor::METHOD_PATCH, [], []);
        $this->assertThat($result, $constraint);

        $result = $this->getTestDumpResult($url, RequestExecutor::METHOD_DELETE, [], []);
        $this->assertThat($result, $constraint);

        $result = $this->getTestDumpResult($url, RequestExecutor::METHOD_POST, [], []);
        $this->assertThat($result, $constraint);
    }

    private function getTestDumpResult(string $url, string $method, array $parameters, array $headers): array
    {
        $responseBody = file_get_contents(__DIR__ . "/response-stubs/normal-response.txt");
        $executor = $this->getMockBuilder(RequestExecutor::class)->getMock();
        HttpRequestWrapper::instance()->replaceExecutor($executor);
        $classMethod = 'makeUrlRequest';
        if (in_array($method, [RequestExecutor::METHOD_POST])) {
            $classMethod = 'makeDataRequest';
        }
        $executor
            ->expects($this->once())
            ->method($classMethod)
            ->with(...[$url, $method, $parameters, $headers])
            ->will($this->returnValue($responseBody));

        ob_start();
        $result = HttpRequestWrapper::dump($url, $method, $parameters, $headers);
        ob_end_clean();

        return [
            $result->arBody['request'][$this->testParameterName],
            $result->arBody['headers'][$this->testHeaderName],
        ];
    }

    public function testDecodeUnicode()
    {
        $unicodeString = '\u041e\u0434\u043d\u0430\u0436\u0434\u044b\u0020\u0432\u0020\u0441\u0442\u0443\u0434\u0435\u043d\u0443\u044e\u0020\u0437\u0438\u043c\u043d\u044e\u044e\u0020\u043f\u043e\u0440\u0443\u002e\u002e\u002e';
        $correctString = 'Однажды в студеную зимнюю пору...';

        $url = self::TEST_HOST . '/CurlShell/RequestExecutorTest/';
        $parameters = [];
        $method = RequestExecutor::METHOD_GET;
        $headers = [];

        $responseBody = "headers [\"$unicodeString\"]";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);

        $this->assertEquals($correctString, $result->arBody[0]);
    }

    public function testParseResponse()
    {
        $url = self::TEST_HOST . '/CurlShell/RequestExecutorTest/';
        $parameters = [];
        $method = RequestExecutor::METHOD_GET;
        $headers = [];

        $correctString = 'Строка';

        $responseBody = "headers $correctString";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);
        $this->assertEmpty($result->arBody);
        $this->assertEmpty($result->rawBody);
        $this->assertEquals($responseBody, $result->rawHeaders);

        $responseBody = "headers [\"$correctString\"]";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);
        $this->assertEquals($correctString, $result->arBody[0]);
        $this->assertEquals('headers', $result->rawHeaders);

        $responseBody = "headers {\"result\": \"$correctString\"}";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);
        $this->assertEquals($correctString, $result->arBody['result']);
        $this->assertEquals('headers', $result->rawHeaders);

        $responseBody = "headers [{\"result\": \"$correctString\"}]";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);
        $this->assertEquals($correctString, $result->arBody[0]['result']);
        $this->assertEquals('headers', $result->rawHeaders);

        $responseBody = "headers {\"result\": [\"$correctString\"]}";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);
        $this->assertEquals($correctString, $result->arBody['result'][0]);
        $this->assertEquals('headers', $result->rawHeaders);

        $responseBody = "headers \n\n $correctString";
        $result = $this->getResultForParseResponse($responseBody, $url, $method, $parameters, $headers);
        $this->assertEquals($correctString, $result->arBody[0]);
        $this->assertEquals($correctString, $result->rawBody);
        $this->assertEquals('headers', $result->rawHeaders);
    }

    private function getResultForParseResponse(
        string $responseBody, string $url, string $method, array $parameters, array $headers
    ): HttpRequestWrapper {
        $executor = $this->getMockBuilder(RequestExecutor::class)->getMock();
        HttpRequestWrapper::instance()->replaceExecutor($executor);
        $executor
            ->expects($this->once())
            ->method('makeUrlRequest')
            ->with(...[$url, $method, $parameters, $headers])
            ->will($this->returnValue($responseBody));
        $result = HttpRequestWrapper::response($url, $method, $parameters, $headers);

        return $result;
    }

}
