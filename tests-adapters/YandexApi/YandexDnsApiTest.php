<?php

namespace YandexApi;

use It5\CurlShell\HttpRequestWrapper;
use It5\Adapters\HttpRequestExecutor\RequestExecutor;
use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use It5\Adapters\YandexApi\YandexDnsApi;
use PHPUnit\Framework\TestCase;

/**
 * Класс тестируется с реальным аккаунтом Яндекс-Днс.
 * При тестах необходимо либо отключить этот тест, либо создать рядом с тестом settings.json, в котором
 * прописать тестовый домен и АПИ-токен
 */
class YandexDnsApiTest extends TestCase
{
    private string $domain;
    private string $token;
    private string $testSubdomain;
    private string $testType;
    private string $testContent;
    private int $testTtl;
    private YandexDnsApi $yandexShell;

    public function setUp(): void
    {
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
        $strDnsData = file_get_contents(__DIR__ . '/settings.json');
        $arDnsData = json_decode($strDnsData, true);
        $this->domain = $arDnsData['domain'];
        $this->token = $arDnsData['token'];
        $this->testSubdomain = '_test.test.test';
        $this->testType = DnsRecordTypesEnum::TXT;
        $this->testTtl = 21600;
        $this->testContent = 'test_content';
        $this->yandexShell = new YandexDnsApi();

        $requestExecutor = new RequestExecutor();
        HttpRequestWrapper::instance()->replaceExecutor($requestExecutor);
    }

    public function testClass()
    {
        $parameters = [
            'domain' => $this->domain,
            'token' => $this->token,
            'type' => $this->testType,
            'subdomain' => $this->testSubdomain,
            'content' => $this->testContent . microtime(true),
            'ttl' => $this->testTtl,
        ];

        $this->yandexShell->delete(...$parameters);

        $result = $this->yandexShell->create(...$parameters);

        $this->assertInstanceOf(DnsRecordDto::class, $result);

        $result = $this->yandexShell->getAll($this->domain, $this->token);

        $this->assertInstanceOf(DnsRecordsCollection::class, $result);
        $this->assertCount(1, $result->filterAnd($this->testSubdomain, $this->testType, ''));

        $parameters['content'] = $this->testContent . microtime(true);
        $this->yandexShell->create(...$parameters);
        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(2, $result->filterAnd($this->testSubdomain, $this->testType, ''));

        $this->yandexShell->delete(...$parameters);
        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(0, $result->filterAnd($this->testSubdomain, $this->testType, ''));
    }
}
