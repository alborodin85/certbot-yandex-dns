<?php

namespace It5\TestsQuick;

use It5\Adapters\CertbotDialog\DialogResultDto;
use It5\CertbotYandexDns;
use It5\CertsCopier\CertsCopier;
use It5\CheckCertNeedUpdate\CertDomainsChecker;
use It5\DebugLibs\DebugLib;
use It5\LongProcesses\DnsParameterWaiter\WaiterSomeDnsRecords;
use It5\ParametersParser\DomainsParametersRegistry;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use PHPUnit\Framework\TestCase;
use It5\CheckCertNeedUpdate\CertDeadlineChecker;
use It5\Adapters\YandexApi\YandexDnsApi;
use It5\Adapters\CertbotDialog\CertbotDialog;
use It5\Adapters\CertbotDialog\DialogDto;
use It5\Env;

class CertbotYandexDnsTest extends TestCase
{
    private function getMockDomainsChecker(): CertDomainsChecker
    {
        $subDomains1 = [
            "it5.su",
            "*.it5.su"
        ];
        $subDomains2 = [
            "it5.team",
            "*.it5.team"
        ];
        $subDomains3 = [
            "s-dver.ru",
            "*.s-dver.ru"
        ];
        $subDomains4 = [
            "dver29spb.ru",
            "*.dver29spb.ru"
        ];
        $domainChecker = $this->getMockBuilder(CertDomainsChecker::class)->getMock();
        $domainChecker->expects($this->exactly(4))
            ->method('getSubdomainsChangesCounts')
            ->withConsecutive(
                ['/etc/letsencrypt/live/it5.su-se/fullchain.pem', $subDomains1, true],
                ['/etc/letsencrypt/live/it5.su-se/fullchain.pem', $subDomains2, true],
                ['/etc/letsencrypt/live/s-dver.ru-se/fullchain.pem', $subDomains3, true],
                ['/etc/letsencrypt/live/dver29spb.ru-se/fullchain.pem', $subDomains4, true],
            )
            ->will($this->onConsecutiveCalls(
                [false, 0, 0],
                [false, 0, 0],
                [true, 1, 1],
                [true, 1, 1],
            ));

        return $domainChecker;
    }

    private function getMockDeadlineChecker(): CertDeadlineChecker
    {
        $deadlineChecker = $this->getMockBuilder(CertDeadlineChecker::class)->getMock();
        $deadlineChecker->expects($this->exactly(2))
            ->method('isPeriodCritical')
            ->withConsecutive(
                ['/etc/letsencrypt/live/it5.su-se/fullchain.pem', 7, true],
                ['/etc/letsencrypt/live/it5.su-se/fullchain.pem', 14, true],
            )
            ->will($this->onConsecutiveCalls(true, false, true));

        return $deadlineChecker;
    }

    private function getMockYandexApi(): YandexDnsApi
    {
        $yandexDnsApi = $this
            ->getMockBuilder(YandexDnsApi::class)
            ->setConstructorArgs([Env::env()->yandexApiDelayMicroseconds])
            ->getMock();
        $yandexDnsApi->expects($this->exactly(6))
            ->method('delete')
            ->withConsecutive(
                ['it5.su', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
                ['it5.su', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
                ['s-dver.ru', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
                ['s-dver.ru', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
                ['dver29spb.ru', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
                ['dver29spb.ru', 'jkdfjkjkhjksdfjkgjksdfjkgf', '_acme-challenge', DnsRecordTypesEnum::TXT],
            )
            ->will($this->onConsecutiveCalls(0, 1, 0, 1, 0, 1));

        $createdRecord = new DnsRecordDto(
            record_id: 1,
            domain: 'domain',
            subdomain: '_acme-challenge',
            fqdn: '',
            type: DnsRecordTypesEnum::TXT,
            content: 'parameter-value',
            priority: 1,
            ttl: 21600,
        );
        $yandexDnsApi->expects($this->any())
            ->method('create')
            ->will($this->returnValue($createdRecord));

        return $yandexDnsApi;
    }

    private function getMockDialog(): CertbotDialog
    {
        $certbotDialog = $this->getMockBuilder(CertbotDialog::class)->getMock();
        $dialogDto = new DialogDto();
        $domains = DomainsParametersRegistry::getCollection();
        $certbotDialog->expects($this->exactly(6))
            ->method('openDialog')
            ->withConsecutive(
                [$domains[0], DebugLib::singleton()->logFile],
                [$domains[0], DebugLib::singleton()->logFile],
                [$domains[2], DebugLib::singleton()->logFile],
                [$domains[2], DebugLib::singleton()->logFile],
                [$domains[3], DebugLib::singleton()->logFile],
                [$domains[3], DebugLib::singleton()->logFile],
            )
            ->will($this->returnValue($dialogDto));
        $certbotDialog->expects($this->exactly(3))
            ->method('getRequiredDnsRecords')
            ->withConsecutive(
                [$dialogDto, $domains[0]],
                [$dialogDto, $domains[2]],
                [$dialogDto, $domains[3]],
            )
            ->will($this->returnValue([
                ['_acme-challenge' => 'parameter-value']
            ]));
        $certbotDialog->expects($this->exactly(6))
            ->method('closeDialog')
            ->with($dialogDto)
            ->will($this->returnValue(null));

        $dialogResult = new DialogResultDto(true, 'certPathResult', 'privKeyResult');
        $certbotDialog->expects($this->exactly(1))
            ->method('startCheckingAndGetResult')
            ->with($dialogDto)
            ->will($this->returnValue($dialogResult));

        $certbotDialog->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($dialogResult));

        $certbotDialog->expects($this->exactly(3))
            ->method('getRequiredDnsRecordsCount')
            ->withConsecutive(
                [$dialogDto, $domains[0]],
                [$dialogDto, $domains[2]],
                [$dialogDto, $domains[3]],
            )
            ->will($this->onConsecutiveCalls(
                1, 0, 1
            ));

        return $certbotDialog;
    }

    private function getMockWaiter(): WaiterSomeDnsRecords
    {
        $waiterDnsRecords = $this
            ->getMockBuilder(WaiterSomeDnsRecords::class)
            ->setConstructorArgs([
                Env::env()->maxWaitingSpreadingSeconds,
                Env::env()->testingSpreadingIntervalSeconds,
                Env::env()->googleDnsServerIp,
            ])
            ->getMock();
        $waiterDnsRecords->expects($this->exactly(3))
            ->method('waitingSomeParameters')
            ->will($this->onConsecutiveCalls(true, false, false));
        Env::env()->additionalWaitingSecs = 0;
        $waiterDnsRecords->expects($this->exactly(2))
            ->method('additionWaiting')
            ->with(0);

        return $waiterDnsRecords;
    }

    private function getMockCopier(): CertsCopier
    {
        $certsCopier = $this
            ->getMockBuilder(CertsCopier::class)
            ->getMock();

        return $certsCopier;
    }

    public function testUpdates()
    {
        $cliArgv = [__FILE__];
        $configAbsolutePath = __DIR__ . '/ParametersParser/DomainParametersStubs/domain-settings-stub.json';
        $logAbsolutePath = __DIR__ . '/test-app.log';
        $object = CertbotYandexDns::singleton(
            $cliArgv,
            $configAbsolutePath,
            $logAbsolutePath,
        );

        $deadlineChecker = $this->getMockDeadlineChecker();
        $object->setDeadlineCheckerMock($deadlineChecker);

        $domainChecker = $this->getMockDomainsChecker();
        $object->setDomainCheckerMock($domainChecker);

        $yandexDnsApi = $this->getMockYandexApi();
        $object->setYandexDnsApiMock($yandexDnsApi);

        $certbotDialog = $this->getMockDialog();
        $object->setCertbotDialogMock($certbotDialog);

        $waiterDnsRecords = $this->getMockWaiter();
        $object->setDnsWaiterMock($waiterDnsRecords);

        $certsCopier = $this->getMockCopier();
        $object->setCertCopier($certsCopier);

        $object->renewCerts();
    }
}
