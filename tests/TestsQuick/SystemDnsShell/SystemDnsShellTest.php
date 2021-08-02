<?php

namespace It5\TestsQuick\SystemDnsShell;

use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\CliCommandExecutor;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use It5\SystemDnsShell\SystemDnsShell;
use PHPUnit\Framework\TestCase;

class SystemDnsShellTest extends TestCase
{
    private SystemDnsShell $dnsShell;

    public function setUp(): void
    {
        DebugLib::init();
        $this->dnsShell = new SystemDnsShell();
    }

    public function testGetDnsParametersQuick()
    {
        $domain = 'it5.su';
        $subdomain = '_yandex_dns_test_existing_parameter';
        $type = DnsRecordTypesEnum::TXT;
        $dnsServerIp = 'dns1.yandex.ru';

        $fullDomain = $subdomain . '.' . $domain . '.';
        $type2 = mb_strtolower($type);
        $correctCommand = "dig +nocmd {$fullDomain} {$type2} +noall +answer @{$dnsServerIp}";

        $executor = $this->getMockBuilder(CliCommandExecutor::class)->getMock();
        $executor->expects($this->once())
            ->method('getCommandResultArray')
            ->with($correctCommand, '')
            ->will($this->returnValue([
                    0 => '_yandex_dns_test_existing_parameter.it5.su. 21600 IN TXT "DO NOT DELETE! Needed for auto-testing!"'
                ])
            );
        $this->dnsShell->setCommandExecutorMock($executor);

        $result = $this->dnsShell->getDnsParameterValues($domain, $subdomain, $type, $dnsServerIp);

        $correctContent = 'DO NOT DELETE! Needed for auto-testing!';

        $this->assertEquals($correctContent, $result[0]->content);
    }

    public function testGetDnsParameters()
    {
        $domain = 'it5.su';
        $subdomain = '_yandex_dns_test_existing_parameter';
        $type = DnsRecordTypesEnum::TXT;
        $dnsServerIp = 'dns1.yandex.ru';

        $this->dnsShell->setCommandExecutorMock(new CliCommandExecutor());
        $result = $this->dnsShell->getDnsParameterValues($domain, $subdomain, $type, $dnsServerIp);

        $correctContent = 'DO NOT DELETE! Needed for auto-testing!';

        $this->assertCount(1, $result);
        $this->assertEquals($correctContent, $result[0]->content);
    }
}
