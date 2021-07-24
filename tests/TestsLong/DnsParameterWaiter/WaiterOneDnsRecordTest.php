<?php

namespace It5\TestsLong\DnsParameterWaiter;

use It5\Adapters\YandexApi\YandexDnsApi;
use It5\DebugLibs\DebugLib;
use It5\Env;
use It5\LongProcesses\DnsParameterWaiter\WaiterOneDnsRecord;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use PHPUnit\Framework\TestCase;

class WaiterOneDnsRecordTest  extends TestCase
{
    private string $domain;
    private string $token;

    public function setUp(): void
    {
        DebugLib::init();

        // Чтобы тестировать необходимо заполнить settings.json по образцу settings.example.json!
        $settings = json_decode(file_get_contents(__DIR__ . "/settings.json"), true);
        $this->domain = $settings['domain'];
        $this->token = $settings['token'];
    }

    public function testAbsentParameter() {
        $parameterName = '_yandex_dns_test_absent_parameter' . microtime(true);
        $parameterValue = '_yandex_dns_test_absent_parameter VALUE';

        $waiter = new WaiterOneDnsRecord(
            30, 1, 'dns1.yandex.ru'
        );

        $recordDto = new DnsRecordDto(
            record_id: 1,
            domain: $this->domain,
            subdomain: $parameterName,
            fqdn: '',
            type: DnsRecordTypesEnum::TXT,
            content: $parameterValue,
            priority: 1,
            ttl: 21600,
        );

        $result = $waiter->waitingOneParameter($recordDto);

        $this->assertFalse($result);
    }

    public function testNewParameter()
    {
        $parameterName = '_yandex_dns_test_new_parameter' . microtime(true);
        $parameterValue = '_yandex_dns_test_new_parameter VALUE';

        $yandexApi = new YandexDnsApi(Env::env()->yandexApiDelayMicroseconds);
        $recordDto = $yandexApi->create(
            $this->domain, $this->token, $parameterName, DnsRecordTypesEnum::TXT, $parameterValue
        );

        $waiter = new WaiterOneDnsRecord(
            Env::env()->maxWaitingSpreadingSeconds, Env::env()->testingSpreadingIntervalSeconds, 'dns1.yandex.ru'
        );

        $result = $waiter->waitingOneParameter($recordDto);

        $yandexApi->delete(
            $this->domain, $this->token, $parameterName, DnsRecordTypesEnum::TXT
        );

        $this->assertTrue($result);
    }

    public function testExistingTxtParameter()
    {
        $parameterName = '_yandex_dns_test_EXISTING_parameter';
        $parameterValue = '_yandex_dns_test_EXISTING_parameter VALUE';

        $waiter = new WaiterOneDnsRecord(
            Env::env()->maxWaitingSpreadingSeconds, 1, 'dns1.yandex.ru'
        );

        $recordDto = new DnsRecordDto(
            record_id: 1,
            domain: $this->domain,
            subdomain: $parameterName,
            fqdn: '',
            type: DnsRecordTypesEnum::TXT,
            content: $parameterValue,
            priority: 1,
            ttl: 21600,
        );

        $result = $waiter->waitingOneParameter($recordDto);

        $this->assertTrue($result);
    }

    public function testExistingAParameter()
    {
        $parameterName = '@';
        $parameterValue = '185.97.200.104';
        $parameterType = DnsRecordTypesEnum::A;

        $waiter = new WaiterOneDnsRecord(
            Env::env()->maxWaitingSpreadingSeconds, 1, 'dns1.yandex.ru'
        );

        $recordDto = new DnsRecordDto(
            record_id: 1,
            domain: 'it5.su',
            subdomain: $parameterName,
            fqdn: '',
            type: $parameterType,
            content: $parameterValue,
            priority: 1,
            ttl: 21600,
        );

        $result = $waiter->waitingOneParameter($recordDto);

        $this->assertTrue($result);
    }
}