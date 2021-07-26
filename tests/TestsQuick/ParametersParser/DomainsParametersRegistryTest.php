<?php

namespace ParametersParser;

use It5\DebugLibs\DebugLib;
use It5\ParametersParser\DomainParametersDto;
use It5\ParametersParser\DomainsParametersCollection;
use It5\ParametersParser\DomainsParametersRegistry;
use PHPUnit\Framework\TestCase;
use It5\Localization\Trans;

class DomainsParametersRegistryTest extends TestCase
{
    private DomainsParametersCollection $collection;

    public function setUp(): void
    {
        DebugLib::init('', DebugLib::MODE_WITH_OUTPUT);
        DomainsParametersRegistry::init(__DIR__ . '/DomainParametersStubs/domain-settings-stub.json');
        $this->collection = DomainsParametersRegistry::getCollection();
    }

    public function tearDown(): void
    {
        DomainsParametersRegistry::reset();
    }

    public function testGetCollection()
    {
        $this->assertInstanceOf(DomainsParametersCollection::class, $this->collection);
    }

    public function testContent()
    {
        $domain = 'it5.su';
        $parameters = DomainsParametersRegistry::getParametersForDomain($domain);

        $this->assertInstanceOf(DomainParametersDto::class, $parameters);
        $this->assertEquals($domain, $parameters->domain);
    }

    public function testEmptyInstance()
    {
        DomainsParametersRegistry::reset();
        $this->expectExceptionMessage(Trans::T('errors.domain_registry_not_inited'));
        DomainsParametersRegistry::getCollection();
    }

    public function testAbsentDomain()
    {
        $domain = 'dver29spb.ru';
        $this->expectExceptionMessage(Trans::T('errors.domain_absent', $domain));
        DomainsParametersRegistry::getParametersForDomain($domain);
    }

    public function testRedundantRecord()
    {
        DomainsParametersRegistry::init(__DIR__ . '/DomainParametersStubs/domain-redundant-settings-stub.json');
        $domain = 'it5.su';
        $this->expectExceptionMessage(Trans::T('errors.domain_redundant_record', $domain));
        DomainsParametersRegistry::getParametersForDomain($domain);
    }

    public function testEmptyParameter()
    {
        $domain = 'it5.su';
        $this->expectExceptionMessage(Trans::T('errors.domain_empty_param', $domain, 'yandexToken'));
        DomainsParametersRegistry::init(__DIR__ . '/DomainParametersStubs/domain-empty-parameters-stub.json');
    }
}
