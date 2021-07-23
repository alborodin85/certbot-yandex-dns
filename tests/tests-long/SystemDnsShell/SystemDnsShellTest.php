<?php

namespace It5\TestsLong\SystemDnsShell;

use It5\Adapters\YandexApi\YandexDnsApi;
use It5\DebugLibs\DebugLib;
use It5\Env;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use It5\SystemDnsShell\SystemDnsShell;
use PHPUnit\Framework\TestCase;

class SystemDnsShellTest extends TestCase
{
    private string $domain;
    private string $token;

    public function setUp(): void
    {
        DebugLib::init();

        // Чтобы тестировать необходимо заполнить settings.json по образцу settings.example.json!
        $settings = json_decode(file_get_contents("settings.json"), true);
        $this->domain = $settings['domain'];
        $this->token = $settings['token'];
    }

    public function testAbsentParameter() {
        $parameterName = '_yandex_dns_test_absent_parameter' . microtime(true);
        $parameterValue = '_yandex_dns_test_absent_parameter VALUE';

        $shell = new SystemDnsShell(
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

        $result = $shell->waitingOneParameter($recordDto);

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

        $shell = new SystemDnsShell(
            Env::env()->maxWaitingSpreadingSeconds, Env::env()->testingSpreadingIntervalSeconds, 'dns1.yandex.ru'
        );

        $result = $shell->waitingOneParameter($recordDto);

        $yandexApi->delete(
            $this->domain, $this->token, $parameterName, DnsRecordTypesEnum::TXT
        );

        $this->assertTrue($result);
    }

    public function testExistingTxtParameter()
    {
        $parameterName = '_yandex_dns_test_existing_parameter';
        $parameterValue = '_yandex_dns_test_existing_parameter VALUE';

        $shell = new SystemDnsShell(
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

        $result = $shell->waitingOneParameter($recordDto);

        $this->assertTrue($result);
    }

    public function testExistingAParameter()
    {
        $parameterName = '@';
        $parameterValue = '185.97.200.104';
        $parameterType = DnsRecordTypesEnum::A;

        $shell = new SystemDnsShell(
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

        $result = $shell->waitingOneParameter($recordDto);

        $this->assertTrue($result);
    }
}
