<?php

namespace SystemDnsShell;

use It5\DebugLibs\DebugLib;
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

    public function testGetDnsParameters()
    {
        $domain = 'it5.team';
        $subDomain = '_acme-challenge';
        $type = DnsRecordTypesEnum::TXT;
        $dnsServerIp = '8.8.8.8';

        $result = $this->dnsShell->getDnsParameterValues($domain, $subDomain, $type, $dnsServerIp);

        $this->assertCount(2, $result);
    }
}
