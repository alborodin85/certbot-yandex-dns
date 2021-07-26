<?php

namespace It5\TestsQuick\SystemDnsShell;

use It5\Adapters\CertbotDialog\DialogDto;
use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;
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

    public function testOffsets()
    {
        $collection = new DnsRecordsCollection($this->recordDto1);

        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset($collection[1]));

        unset($collection[0]);
        $this->assertFalse(isset($collection[0]));

        $collection[] = $this->recordDto1;
        $collection[5] = $this->recordDto2;

        $this->assertCount(2, $collection);
    }

    public function testToArray()
    {
        $correct = [
            $this->recordDto1,
        ];
        $collection = new DnsRecordsCollection($this->recordDto1);

        $this->assertEquals($correct, $collection->toArray());
    }

    public function testGetValuesContents()
    {
        $correct = [
            1 => $this->recordDto1->content,
            2 => $this->recordDto2->content,
        ];
        $collection = new DnsRecordsCollection($this->recordDto1, $this->recordDto2);
        $this->assertEquals($correct, $collection->getValuesContent());
    }

    public function testFilterAnd()
    {
        $collection = new DnsRecordsCollection($this->recordDto1, $this->recordDto2);
        $filtered = $collection->filterAnd(
            $this->recordDto1->subdomain,
            $this->recordDto1->type,
            $this->recordDto1->content
        );

        $this->assertCount(1, $filtered);
        $this->assertEquals($this->recordDto1->uuid(), $filtered[0]->uuid());
    }

    public function testSetInvalidType()
    {
        $collection = new DnsRecordsCollection();
        $this->expectError();
        $collection[3] = new DialogDto();
    }

    public function testDtoToArray()
    {
        $correct = [
            'record_id' => '1',
            'domain' => 'domain',
            'subdomain' => 'subdomain1',
            'fqdn' => '',
            'type' => 'type',
            'content' => 'value1',
            'priority' => '1',
            'ttl' => '21600',
            'token' => '',
        ];

        $this->assertEquals($correct, $this->recordDto1->toArray());
    }

    public function testUniqueness()
    {
        $collection = new DnsRecordsCollection($this->recordDto1, $this->recordDto1);
        $this->assertCount(1, $collection);
        $collection->add($this->recordDto1);
        $this->assertCount(1, $collection);
        $collection[] = $this->recordDto1;
        $this->assertCount(1, $collection);

        $this->expectError();
        $collection[1] = $this->recordDto1;
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
