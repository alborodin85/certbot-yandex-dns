<?php

namespace It5\TestsQuick;

use It5\CertbotYandexDns;
use It5\DebugLibs\DebugLib;
use It5\LongProcesses\DnsParameterWaiter\WaiterSomeDnsRecords;
use It5\ParametersParser\DomainsParametersRegistry;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use phpDocumentor\Reflection\Types\True_;
use PHPUnit\Framework\TestCase;
use It5\CheckCertNeedUpdate\CertDeadlineChecker;
use It5\Adapters\YandexApi\YandexDnsApi;
use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\Adapters\CertbotDialog\DialogDto;
use It5\Env;

class CertbotYandexDnsTest extends TestCase
{
    public function testUpdates()
    {
        $cliArgv = [__FILE__];
        $configAbsolutePath = __DIR__ . '/ParametersParser/DomainParametersStubs/domain-settings-stub.json';
        $logAbsolutePath = __DIR__ . 'test-app.log';
        $object = CertbotYandexDns::singleton($cliArgv, $configAbsolutePath, $logAbsolutePath, Env::env()->yandexApiDelayMicroseconds);

        $checker = $this->getMockBuilder(CertDeadlineChecker::class)->getMock();
        $checker->expects($this->exactly(3))
            ->method('check')
            ->withConsecutive(
                ['/etc/letsencrypt/live/it5.su-se/fullchain.pem', 7],
                ['/etc/letsencrypt/live/it5.su-se/fullchain.pem', 14],
                ['/etc/letsencrypt/live/s-dver.ru-se/fullchain.pem', 10],
            )
            ->will($this->onConsecutiveCalls(true, false, true));
        $object->setDeadlineCheckerMock($checker);

        $yandexDnsApi = $this
            ->getMockBuilder(YandexDnsApi::class)
            ->setConstructorArgs([Env::env()->yandexApiDelayMicroseconds])
            ->getMock();
        $yandexDnsApi->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['it5.su', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
                ['s-dver.ru', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
            )
            ->will($this->onConsecutiveCalls(0, 1));
        $object->setYandexDnsApiMock($yandexDnsApi);

        $certbotDialog = $this->getMockBuilder(CertbotDialog::class)->getMock();
        $dialogDto = new DialogDto();
        $domains = DomainsParametersRegistry::getCollection();
        $certbotDialog->expects($this->exactly(2))
            ->method('openDialog')
            ->withConsecutive(
                [$domains[0], DebugLib::singleton()->logFile],
                [$domains[2], DebugLib::singleton()->logFile],
            )
            ->will($this->returnValue($dialogDto));
        $certbotDialog->expects($this->exactly(2))
            ->method('getRequiredDnsRecords')
            ->withConsecutive(
                [$dialogDto, $domains[0]],
                [$dialogDto, $domains[2]],
            )
            ->will($this->returnValue([
                ['parameter-name' => 'parameter-value']
            ]));
        $certbotDialog->expects($this->exactly(2))
            ->method('closeDialog')
            ->with($dialogDto)
            ->will($this->returnValue(null));
        $certbotDialog->expects($this->once())
            ->method('startCheckingAndGetResult')
            ->with($dialogDto)
            ->will($this->returnValue(null));
        $object->setCertbotDialogMock($certbotDialog);

        $waiterDnsRecords = $this
            ->getMockBuilder(WaiterSomeDnsRecords::class)
            ->setConstructorArgs([
                Env::env()->maxWaitingSpreadingSeconds,
                Env::env()->testingSpreadingIntervalSeconds,
                Env::env()->googleDnsServerIp,
            ])
            ->getMock();
        $waiterDnsRecords->expects($this->exactly(2))
            ->method('waitingSomeParameters')
            ->will($this->onConsecutiveCalls(true, false));
        $object->setDnsWaiterMock($waiterDnsRecords);

        $object->renewCerts();
    }
}
