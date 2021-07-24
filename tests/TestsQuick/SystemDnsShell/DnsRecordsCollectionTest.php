<?php

namespace It5\TestsQuick\SystemDnsShell;

use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

class DnsRecordsCollectionTest extends TestCase
{
    private Constraint $constAdd;
    private DnsRecordDto $recordDto1;
    private DnsRecordDto $recordDto2;

    protected function setUp(): void
    {
        DebugLib::init();

        $this->recordDto1 = new DnsRecordDto(
            record_id: 1,
            domain: 'domain',
            subdomain: 'subdomain1',
            fqdn: '',
            type: 'type',
            content: 'value1',
            priority: 1,
            ttl: 21600,
        );

        $this->recordDto2 = new DnsRecordDto(
            record_id: 2,
            domain: 'domain',
            subdomain: 'subdomain2',
            fqdn: '',
            type: 'type',
            content: 'value2',
            priority: 1,
            ttl: 21600,
        );

        $this->constAdd = $this->logicalAnd(
            $this->containsEqual($this->recordDto1),
            $this->containsEqual($this->recordDto2),
            $this->countOf(2),
        );
    }

    public function testAddOne()
    {
        $collection = new DnsRecordsCollection();
        $collection->add($this->recordDto1);

        $this->assertEquals($this->recordDto1, $collection[0]);
    }

    public function testConstructOne()
    {
        $collection = new DnsRecordsCollection($this->recordDto2);

        $this->assertEquals($this->recordDto2, $collection[0]);
    }

    public function testAddSome()
    {
        $collection = new DnsRecordsCollection();
        $collection->add($this->recordDto1, $this->recordDto2);

        $this->assertThat($collection, $this->constAdd);
    }

    public function testConstructSome()
    {
        $collection = new DnsRecordsCollection($this->recordDto1, $this->recordDto2);
        $this->assertThat($collection, $this->constAdd);
    }
}
