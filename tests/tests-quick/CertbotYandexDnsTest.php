<?php

use It5\CertbotYandexDns;
use PHPUnit\Framework\TestCase;
use It5\CertUpdateSteps\CheckCertDeadline\CertDeadlineChecker;
use It5\Adapters\YandexApi\YandexDnsApi;
use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\Adapters\CertbotDialog\DialogDto;
use It5\Env;

class CertbotYandexDnsTest extends TestCase
{
    public function testClass()
    {
        $cliArgv = [__FILE__];
        $configAbsolutePath = __DIR__ . '/ParametersParser/DomainParametersStubs/domain-settings-stub.json';
        $logAbsolutePath = __DIR__ . 'test-app.log';
        $object = CertbotYandexDns::singleton($cliArgv, $configAbsolutePath, $logAbsolutePath, Env::env()->yandexApiDelayMicroseconds);

        $checker = $this->getMockBuilder(CertDeadlineChecker::class)->getMock();
        $checker->expects($this->any())
            ->method('check')
            ->will($this->onConsecutiveCalls(true, false))
        ;
        $object->replaceDeadlineChecker($checker);

        $yandexDnsApi = $this->getMockBuilder(YandexDnsApi::class);
        $yandexDnsApi = $this
            ->getMockBuilder(YandexDnsApi::class)
            ->setConstructorArgs([Env::env()->yandexApiDelayMicroseconds])
            ->getMock();

        $yandexDnsApi->expects($this->any())
            ->method('delete')
            ->will($this->onConsecutiveCalls(0, 1));
        $object->replaceYandexDnsApi($yandexDnsApi);

        $certbotDialog = $this->getMockBuilder(CertbotDialog::class)->getMock();
        $certbotDialog->expects($this->any())
            ->method('openDialog')
            ->will($this->returnValue(new DialogDto()));
        $certbotDialog->expects($this->any())
            ->method('getRequiredDnsRecords')
            ->will($this->returnValue([
                ['parameter-name' => 'parameter-value']
            ]));
        $object->replaceCertbotDialog($certbotDialog);

        $result = $object->renewCerts();
        $this->assertTrue($result);
    }
}
