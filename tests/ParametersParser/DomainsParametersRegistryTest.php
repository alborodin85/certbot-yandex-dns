<?php

namespace ParametersParser;

use It5\ParametersParser\DomainParametersDto;
use It5\ParametersParser\DomainsParametersCollection;
use It5\ParametersParser\DomainsParametersRegistry;
use It5\Localization\Ru;
use PHPUnit\Framework\TestCase;

class DomainsParametersRegistryTest extends TestCase
{
    private DomainsParametersCollection $collection;

    public function setUp(): void
    {
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
        $this->expectExceptionMessage(Ru::get('errors.cli_registry_not_inited'));
        DomainsParametersRegistry::getCollection();
    }

    public function testAbsentDomain()
    {
        $domain = 's-dver.ru';
        $this->expectExceptionMessage(Ru::get('errors.domain_absent', $domain));
        DomainsParametersRegistry::getParametersForDomain($domain);
    }

    public function testRedundantRecord()
    {
        DomainsParametersRegistry::init(__DIR__ . '/DomainParametersStubs/domain-redundant-settings-stub.json');
        $domain = 'it5.su';
        $this->expectExceptionMessage(Ru::get('errors.domain_redundant_record', $domain));
        DomainsParametersRegistry::getParametersForDomain($domain);
    }

    public function testEmptyParameter()
    {
        $domain = 'it5.su';
        $this->expectExceptionMessage(Ru::get('errors.domain_empty_param', $domain, 'yandexToken'));
        DomainsParametersRegistry::init(__DIR__ . '/DomainParametersStubs/domain-empty-parameters-stub.json');
    }
}