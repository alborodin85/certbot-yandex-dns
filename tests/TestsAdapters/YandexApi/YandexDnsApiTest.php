<?php

namespace It5\TestsAdapters\YandexApi;

use It5\CurlShell\HttpRequestWrapper;
use It5\Adapters\HttpRequestExecutor\RequestExecutor;
use It5\DebugLibs\DebugLib;
use It5\Env;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\DnsRecordTypesEnum;
use It5\Adapters\YandexApi\YandexDnsApi;
use PHPUnit\Framework\TestCase;

/**
 * Класс тестируется с реальным аккаунтом Яндекс-Днс.
 * При тестах необходимо либо отключить этот тест, либо создать рядом с тестом settings.json, в котором
 * прописать тестовый домен и АПИ-токен
 */
class YandexDnsApiTest extends TestCase
{
    private string $domain;
    private string $token;
    private string $testSubdomain;
    private string $cnameSubdomain;
    private string $testType;
    private string $testContent;
    private int $testTtl;
    private YandexDnsApi $yandexShell;
    private array $recordsCommonFields;

    public function setUp(): void
    {
        DebugLib::init();
        $strDnsData = file_get_contents(__DIR__ . '/settings.json');
        $arDnsData = json_decode($strDnsData, true);
        $this->domain = $arDnsData['domain'];
        $this->token = $arDnsData['token'];
        $this->testSubdomain = '_test.test.test';
        $this->cnameSubdomain = '_test';
        $this->testType = DnsRecordTypesEnum::TXT;
        $this->testTtl = 21600;
        $this->testContent = 'test_content';
        $this->yandexShell = new YandexDnsApi(Env::env()->yandexApiDelayMicroseconds);

        $requestExecutor = new RequestExecutor();
        HttpRequestWrapper::instance()->replaceExecutor($requestExecutor);

        $this->recordsCommonFields = [
            'record_id' => 1,
            'domain' => $this->domain,
            'subdomain' => $this->testSubdomain,
            'fqdn' => '',
            'type' => $this->testType,
            'content' => $this->testContent . microtime(true),
            'priority' => 10,
            'ttl' => $this->testTtl,
        ];
    }

    protected function onNotSuccessfulTest(\Throwable $t): void
    {
        $newRecords = new DnsRecordsCollection(
            ...$this->existsRecordsForTestsSync()->toArray(),
            ...$this->getNewRecordsForTestSync()->toArray(),
        );
        $this->yandexShell->deleteSome($newRecords, $this->token);

        throw $t;
    }

    public function testSyncRecordNothing()
    {
        $existsRecords = $this->existsRecordsForTestsSync();
        $this->yandexShell->createSome($existsRecords, $this->token);

        $newRecords = new DnsRecordsCollection(
            ...$this->existsRecordsForTestsSync()->toArray(),
        );
        [$countAdded, $countDeleted] = $this->yandexShell->syncRecords($this->domain, $this->token, $newRecords);
        $this->assertEquals(0, $countAdded);
        $this->assertEquals(0, $countDeleted);

        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(2, $result->filterAnd($this->testSubdomain, $this->testType, ''));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[0]->uuid()));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[1]->uuid()));

        $this->yandexShell->deleteSome($newRecords, $this->token);
    }

    public function testSyncRecordsDeleting()
    {
        $existsRecords = new DnsRecordsCollection(
            ...$this->existsRecordsForTestsSync()->toArray(),
            ...$this->getNewRecordsForTestSync()->toArray(),
        );
        $this->yandexShell->createSome($existsRecords, $this->token);
        $newRecords = new DnsRecordsCollection(
            ...$this->getNewRecordsForTestSync()->toArray(),
        );
        [$countAdded, $countDeleted] = $this->yandexShell->syncRecords($this->domain, $this->token, $newRecords);
        $this->assertEquals(0, $countAdded);
        $this->assertEquals(2, $countDeleted);

        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(1, $result->filterAnd($this->testSubdomain, $this->testType, ''));
        $this->assertCount(1, $result->filterAnd($this->cnameSubdomain, DnsRecordTypesEnum::CNAME, ''));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[0]->uuid()));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[1]->uuid()));

        $this->yandexShell->deleteSome($existsRecords, $this->token);
    }

    public function testSyncRecordsAdding()
    {
        $existsRecords = $this->existsRecordsForTestsSync();
        $this->yandexShell->createSome($existsRecords, $this->token);

        $newRecords = new DnsRecordsCollection(
            ...$this->existsRecordsForTestsSync()->toArray(),
            ...$this->getNewRecordsForTestSync()->toArray(),
        );
        [$countAdded, $countDeleted] = $this->yandexShell->syncRecords($this->domain, $this->token, $newRecords);
        $this->assertEquals(2, $countAdded);
        $this->assertEquals(0, $countDeleted);

        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(3, $result->filterAnd($this->testSubdomain, $this->testType, ''));
        $this->assertCount(1, $result->filterAnd($this->cnameSubdomain, DnsRecordTypesEnum::CNAME, ''));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[0]->uuid()));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[1]->uuid()));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[2]->uuid()));
        $this->assertInstanceOf(DnsRecordDto::class, $result->findByUuid($newRecords[3]->uuid()));

        $this->yandexShell->deleteSome($newRecords, $this->token);
    }

    private function existsRecordsForTestsSync(): DnsRecordsCollection
    {
        static $result;
        if ($result instanceof DnsRecordsCollection) {
            return $result;
        }
        // $existsRecord1
        $parameters = $this->recordsCommonFields;
        $parameters['record_id'] = 1;
        $parameters['content'] = '$existsRecord1' . '_' . microtime(true);
        $existsRecord1 = new DnsRecordDto(...$parameters);

        // $existsRecord2
        $parameters = $this->recordsCommonFields;
        $parameters['record_id'] = 2;
        $parameters['content'] = '$existsRecord2' . '_' . microtime(true);
        $existsRecord2 = new DnsRecordDto(...$parameters);

        $result = new DnsRecordsCollection($existsRecord1, $existsRecord2);

        return $result;
    }

    private function getNewRecordsForTestSync(): DnsRecordsCollection
    {
        static $result;
        if ($result instanceof DnsRecordsCollection) {
            return $result;
        }

        // $newRecord1
        $parameters = $this->recordsCommonFields;
        $parameters['record_id'] = 3;
        $parameters['content'] = '$newRecord1' . '_' . microtime(true);
        $newRecord1 = new DnsRecordDto(...$parameters);

        // $newRecord2
        $parameters = $this->recordsCommonFields;
        $parameters['record_id'] = 4;
        $parameters['content'] = $this->domain . '.';
        $parameters['type'] = DnsRecordTypesEnum::CNAME;
        $parameters['subdomain'] = $this->cnameSubdomain;
        $newRecord2 = new DnsRecordDto(...$parameters);

        $result = new DnsRecordsCollection($newRecord1, $newRecord2);

        return $result;
    }

    public function testCreateDeleteSome()
    {
        $parameters = $this->recordsCommonFields;
        $record1 = new DnsRecordDto(...$parameters);
        $parameters['record_id'] = 2;
        $parameters['content'] = $this->testContent . '_2_' . microtime(true);
        $record2 = new DnsRecordDto(...$parameters);
        $recordsCollection = new DnsRecordsCollection($record1, $record2);

        $result = $this->yandexShell->createSome($recordsCollection, $this->token);

        $this->assertCount(2, $result->filterAnd($this->testSubdomain, $this->testType, ''));

        $this->yandexShell->deleteSome($recordsCollection, $this->token);

        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(0, $result->filterAnd($this->testSubdomain, $this->testType, ''));
    }

    public function testCreateTwoRecords()
    {
        $subdomain = '_acme-challenge';

        $parameters = [
            'domain' => $this->domain,
            'token' => $this->token,
            'type' => $this->testType,
            'subdomain' => $subdomain,
            'content' => $this->testContent . microtime(true),
            'ttl' => $this->testTtl,
        ];

        $parameters['content'] = '';
        $this->yandexShell->delete(...$parameters);

        $parameters['content'] = $this->testContent . '_1_' . microtime(true);
        $result1 = $this->yandexShell->create(...$parameters);
        $this->assertTrue(!!$result1->record_id);

        $parameters['content'] = $this->testContent . '_2_' . microtime(true);
        $result2 = $this->yandexShell->create(...$parameters);
        $this->assertTrue(!!$result2->record_id);

        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(2, $result->filterAnd($subdomain, $this->testType, ''));

        $this->yandexShell->deleteSome(new DnsRecordsCollection($result1, $result2), $this->token);
    }

    public function testClass()
    {
        $parameters = [
            'domain' => $this->domain,
            'token' => $this->token,
            'type' => $this->testType,
            'subdomain' => $this->testSubdomain,
            'content' => $this->testContent . microtime(true),
            'ttl' => $this->testTtl,
        ];

        $this->yandexShell->delete(...$parameters);

        $result = $this->yandexShell->create(...$parameters);

        $this->assertInstanceOf(DnsRecordDto::class, $result);

        $result = $this->yandexShell->getAll($this->domain, $this->token);

        $this->assertInstanceOf(DnsRecordsCollection::class, $result);
        $this->assertCount(1, $result->filterAnd($this->testSubdomain, $this->testType, ''));

        $parameters['content'] = $this->testContent . microtime(true);
        $this->yandexShell->create(...$parameters);
        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(2, $result->filterAnd($this->testSubdomain, $this->testType, ''));

        $parameters['content'] = '';
        $this->yandexShell->delete(...$parameters);
        $result = $this->yandexShell->getAll($this->domain, $this->token);
        $this->assertCount(0, $result->filterAnd($this->testSubdomain, $this->testType, ''));
    }
}
