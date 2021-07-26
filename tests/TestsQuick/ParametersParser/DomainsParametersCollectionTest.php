<?php

namespace It5\TestsQuick\ParametersParser;

use It5\ParametersParser\DomainParametersDto;
use It5\ParametersParser\DomainsParametersCollection;
use It5\ParametersParser\DomainsParametersError;
use PHPUnit\Framework\TestCase;

class DomainsParametersCollectionTest extends TestCase
{
    private DomainsParametersCollection $collection;

    public function setUp(): void
    {
        $this->collection = new DomainsParametersCollection();
        $dtoParams = [
            'id' => 1,
            'domain' => 'domain',
            'subDomains' => ['subDomains'],
            'adminEmail' => 'adminEmail',
            'yandexToken' => 'yandexToken',
            'criticalRemainingDays' => 7,
            'certPath' => 'certPath',
            'dnsParameterName' => 'dnsParameterName',
            'isDryRun' => true,
            'isForceRenewal' => true,
            'isSudoMode' => true,
        ];
        $this->collection->add(new DomainParametersDto(...$dtoParams));
        $dtoParams['id'] = 2;
        $this->collection->add(new DomainParametersDto(...$dtoParams));
        $dtoParams['id'] = 3;
        $this->collection[3] = new DomainParametersDto(...$dtoParams);
        $dtoParams['id'] = 4;
        $this->collection[4] = new DomainParametersDto(...$dtoParams);
        $dtoParams['id'] = 5;
        $this->collection[] = new DomainParametersDto(...$dtoParams);
    }

    public function testDefaultValues()
    {
        $dtoParams = [
            'id' => 6,
            'domain' => 'domain',
            'subDomains' => ['subDomains'],
            'adminEmail' => 'adminEmail',
            'yandexToken' => 'yandexToken',
            'certPath' => 'certPath',
        ];
        $collection = new DomainsParametersCollection();
        $collection[] = new DomainParametersDto(...$dtoParams);
        $this->assertCount(1, $collection);
    }

    public function testClass()
    {
        $this->assertIsIterable($this->collection);
        $this->assertEquals(5, $this->collection[5]->id);
        $this->assertCount(5, $this->collection);
        unset($this->collection[5]);
        $this->assertCount(4, $this->collection);

        foreach ($this->collection as $item) {
            $this->assertInstanceOf(DomainParametersDto::class, $item);
        }
    }

    public function testOffsetExists()
    {
        $this->assertEquals(true, $this->collection->offsetExists(0));
        $this->assertEquals(false, $this->collection->offsetExists(7));
        $this->assertInstanceOf(DomainParametersDto::class, $this->collection[0]);

        $this->expectError();
        $this->collection[7];
    }

    public function testOffsetSet()
    {
        $this->expectException(DomainsParametersError::class);
        $this->collection[8] = 3;
    }
}
