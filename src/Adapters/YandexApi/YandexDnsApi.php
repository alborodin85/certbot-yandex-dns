<?php

namespace It5\Adapters\YandexApi;

use It5\CurlShell\HttpRequestWrapper;
use It5\Adapters\HttpRequestExecutor\RequestExecutor;
use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;

class YandexDnsApi
{
    public function __construct(
        private int $delayMicroseconds,
    ) { }

    /**
     * Записи считаются Идентичными, если у них одинаковые поддомен, тип и значение
     * Для каждой новой записи из $newRecords:
     * - если в зоне нет идентичной записи, то запись добавляется
     * - если в зоне есть идентичная запись, то ничего не делается
     * - если в зоне есть записи для такого же поддомена и такого же типа, но с другим значением то они удаляются
     */
    public function syncRecords(string $domain, string $token, DnsRecordsCollection $newRecords): array
    {
        $allRecords = $this->getAll($domain, $token);
        // если в зоне нет идентичной записи, то запись добавляется
        $countAdded = 0;
        foreach ($newRecords as $newRecordItem) {
            $existsRecordItem = $allRecords->findByUuid($newRecordItem->uuid());
            if ($existsRecordItem) {
                continue;
            }
            $this->create(
                $domain, $token, $newRecordItem->subdomain, $newRecordItem->type, $newRecordItem->content
            );
            $countAdded++;
        }

        // если в зоне есть записи для такого же поддомена и такого же типа, но с другим значением то они удаляются
        $arNewContents = [];
        foreach ($newRecords as $recordDto) {
            $arNewContents[$recordDto->subdomain . '_' . $recordDto->type][] = $recordDto->content;
        }
        $arExistsContents = [];
        foreach ($allRecords as $recordDto) {
            $arExistsContents[$recordDto->subdomain . '_' . $recordDto->type][] = $recordDto->content;
        }
        $arExistsKeys = [];
        foreach ($allRecords as $recordDto) {
            $arExistsKeys[$recordDto->subdomain . '_' . $recordDto->type][] = $recordDto->uuid();
        }

        $countDeleted = 0;
        foreach ($arNewContents as $domainType => $arNewContentsItem) {
            if (!isset($arExistsContents[$domainType])) {
                continue;
            }
            $arUnnecessaryRecordsContents = array_diff($arExistsContents[$domainType], $arNewContentsItem);
            $arUnnecessaryRecordsKeys = array_keys($arUnnecessaryRecordsContents);

            $recordsUuidsForDelete = array_filter(
                $arExistsKeys[$domainType],
                fn ($k) => in_array($k, $arUnnecessaryRecordsKeys),
                ARRAY_FILTER_USE_KEY
            );
            foreach ($recordsUuidsForDelete as $recordUuid) {
                $recordDto = $allRecords->findByUuid($recordUuid);
                $recordDto->token = $token;
                $this->delete(...$recordDto->toArray());
                $countDeleted++;
            }
        }

        return [$countAdded, $countDeleted];
    }

    public function getAll(string $domain, string $token): DnsRecordsCollection
    {
        $result = new DnsRecordsCollection();

        $url = 'https://pddimp.yandex.ru/api2/admin/dns/list';
        $method = RequestExecutor::METHOD_GET;
        $parameters = ['domain' => $domain];
        $headers = ['PddToken' => $token];
        $records = $this->makeRequest($url, $method, $parameters, $headers);

        $arRecords = $records->arBody['records'] ?? [];

        foreach ($arRecords as $arDnsItem) {
            $dnsRecordDto = new DnsRecordDto(...$arDnsItem);
            $result->add($dnsRecordDto);
        }

        return $result;
    }

    public function create(
        string $domain, string $token, string $subdomain, string $type, string $content, ...$any
    ): DnsRecordDto {
        $parameters = [
            'domain' => '',
            'type' => '',
            'subdomain' => '',
            'content' => '',
            'ttl' => '',
            'fqdn' => '',
            'record_id' => '',
            'priority' => '',
        ];
        $result = new DnsRecordDto(...$parameters);

        $url = 'https://pddimp.yandex.ru/api2/admin/dns/add';
        $method = RequestExecutor::METHOD_POST;
        $parameters = [
            'domain' => $domain,
            'type' => $type,
            'content' => $content,
            'subdomain' => $subdomain,
        ];
        $headers = ['PddToken' => $token];
        $rawRecord = $this->makeRequest($url, $method, $parameters, $headers);

        $rawRecord = $rawRecord->arBody['record'] ?? false;

        if ($rawRecord) {
            $result = new DnsRecordDto(...$rawRecord);
        }

        return $result;
    }

    public function createSome(DnsRecordsCollection $recordsForCreate, string $token): DnsRecordsCollection
    {
        $result = new DnsRecordsCollection();
        foreach ($recordsForCreate as $recordDto) {
            $recordDto->token = $token;
            $newRecord = $this->create(...$recordDto->toArray());
            $result[] = $newRecord;
        }

        return $result;
    }

    /**
     * Если $subdomain, $type или $content передать пустые строки, то они в фильтре не участвуют
     */
    public function delete(
        string $domain, string $token, string $subdomain, string $type, string $content, ...$any
    ): int {
        $records = $this->getAll($domain, $token);
        $recordsForDeleting = $records->filterAnd($subdomain, $type, $content);

        foreach ($recordsForDeleting as $recordDto) {
            $url = 'https://pddimp.yandex.ru/api2/admin/dns/del';
            $method = RequestExecutor::METHOD_POST;
            $parameters = ['domain' => $domain, 'record_id' => $recordDto->record_id];
            $headers = ['PddToken' => $token];
            $this->makeRequest($url, $method, $parameters, $headers);
        }

        return count($recordsForDeleting);
    }

    public function deleteSome(DnsRecordsCollection $recordsForDelete, string $token)
    {
        foreach ($recordsForDelete as $recordDto) {
            $recordDto->token = $token;
            $this->delete(...$recordDto->toArray());
        }
    }

    private function makeRequest(string $url, string $method, array $parameters, array $headers): HttpRequestWrapper
    {
        usleep($this->delayMicroseconds);
        $rawRecord = HttpRequestWrapper::response($url, $method, $parameters, $headers);

        return $rawRecord;
    }
}