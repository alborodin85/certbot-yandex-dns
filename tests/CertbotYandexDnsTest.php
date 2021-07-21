<?php

use It5\CertbotYandexDns;
use PHPUnit\Framework\TestCase;
use It5\CertUpdateSteps\CheckCertDeadline\CertDeadlineChecker;
use It5\YandexApi\YandexDnsApi;

class CertbotYandexDnsTest extends TestCase
{
    public function testClass()
    {
        $cliArgv = [__FILE__];
        $configAbsolutePath = __DIR__ . '/ParametersParser/DomainParametersStubs/domain-settings-stub.json';
        $logAbsolutePath = __DIR__ . 'test-app.log';
        $object = CertbotYandexDns::singleton($cliArgv, $configAbsolutePath, $logAbsolutePath);

        $checker = $this->getMockBuilder(CertDeadlineChecker::class)->getMock();
        $checker->expects($this->any())
            ->method('check')
            ->will($this->onConsecutiveCalls(true, false))
        ;
        $object->replaceDeadlineChecker($checker);
        $result = $object->renewCerts();

        $yandexDnsApi = $this->getMockBuilder(YandexDnsApi::class)->getMock();
        $yandexDnsApi->expects($this->any())
            ->method('delete')
            ->will($this->onConsecutiveCalls(0, 1));
        $object->replaceYandexDnsApi($yandexDnsApi);

        $this->assertTrue($result);
    }
}
