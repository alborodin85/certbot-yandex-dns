<?php

namespace It5\TestsLong\DnsParameterWaiter;

use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use PHPUnit\Framework\TestCase;
use It5\Adapters\YandexApi\YandexDnsApi;
use It5\Env;
use It5\LongProcesses\DnsParameterWaiter\WaiterSomeDnsRecords;
use It5\SystemDnsShell\DnsRecordsCollection;

class WaiterSomeDnsRecordsTest extends TestCase
{
    private string $domain;
    private string $token;

    private string $parameterName1;
    private string $parameterName2;

    private DnsRecordDto $goodRecordDto1;
    private DnsRecordDto $goodRecordDto2;
    private DnsRecordDto $badRecordDto;

    private YandexDnsApi $yandexApi;

    public function setUp(): void
    {
        DebugLib::init();
        $this->yandexApi = new YandexDnsApi(Env::env()->yandexApiDelayMicroseconds);

        // Чтобы тестировать, необходимо заполнить settings.json по образцу settings.example.json!
        $settings = json_decode(file_get_contents(__DIR__ . "/settings.json"), true);
        $this->domain = $settings['domain'];
        $this->token = $settings['token'];

        $this->parameterName1 = '_yandex_dns_test_new_parameter_1_' . microtime(true);
        $parameterValue1 = '_yandex_dns_test_new_parameter_1 VALUE';
        $this->goodRecordDto1 = $this->yandexApi->create(
            $this->domain,
            $this->token,
            $this->parameterName1,
            DnsRecordTypesEnum::TXT,
            $parameterValue1,
        );

        $this->parameterName2 = '_yandex_dns_test_new_parameter_2_' . microtime(true);
        $parameterValue2 = '185.97.200.105';
        $this->goodRecordDto2 = $this->yandexApi->create(
            $this->domain,
            $this->token,
            $this->parameterName2,
            DnsRecordTypesEnum::A,
            $parameterValue2,
        );

        $this->badRecordDto = new DnsRecordDto(
            record_id: 3,
            domain: $this->domain,
            subdomain: 'absent-test-record',
            fqdn: '',
            type: DnsRecordTypesEnum::AAAA,
            content: 'value3',
            priority: 1,
            ttl: 21600,
        );
    }

    protected function tearDown(): void
    {
        $this->yandexApi->delete(
            $this->domain,
            $this->token,
            $this->parameterName1,
            DnsRecordTypesEnum::TXT,
            '',
        );

        $this->yandexApi->delete(
            $this->domain,
            $this->token,
            $this->parameterName2,
            DnsRecordTypesEnum::A,
            '',
        );
    }

    public function testWaitingEmptyCollection()
    {
        $waiter = new WaiterSomeDnsRecords(
            Env::env()->maxWaitingSpreadingSeconds, 1, 'dns1.yandex.ru'
        );

        $recordsCollection = new DnsRecordsCollection();
        $result = $waiter->waitingSomeParameters($recordsCollection);

        $this->assertFalse($result);
    }

    public function testOneRecord()
    {
        $waiter = new WaiterSomeDnsRecords(
            Env::env()->maxWaitingSpreadingSeconds, Env::env()->testingSpreadingIntervalSeconds, 'dns1.yandex.ru'
        );

        $recordsCollection = new DnsRecordsCollection($this->goodRecordDto1);
        $result = $waiter->waitingSomeParameters($recordsCollection);

        $this->assertTrue($result);
    }

    public function testAbsentRecord()
    {
        $waiter = new WaiterSomeDnsRecords(
            60, 2, 'dns1.yandex.ru'
        );

        $recordsCollection = new DnsRecordsCollection($this->goodRecordDto1, $this->goodRecordDto2);
        $recordsCollection->add($this->badRecordDto);
        $result = $waiter->waitingSomeParameters($recordsCollection);

        $this->assertFalse($result);
    }

    public function testTwoCorrectRecords()
    {
        $waiter = new WaiterSomeDnsRecords(
            Env::env()->maxWaitingSpreadingSeconds, Env::env()->testingSpreadingIntervalSeconds, 'dns1.yandex.ru'
        );

        $recordsCollection = new DnsRecordsCollection($this->goodRecordDto1, $this->goodRecordDto2);
        $result = $waiter->waitingSomeParameters($recordsCollection);

        $this->assertTrue($result);
    }
}
