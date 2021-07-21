<?php

namespace It5\Adapters\YandexApi;

use It5\CurlShell\HttpRequestWrapper;
use It5\Adapters\HttpRequestExecutor\RequestExecutor;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;

class YandexDnsApi
{
    public function getAll(string $domain, string $token): DnsRecordsCollection
    {
        $result = new DnsRecordsCollection();

        $url = 'https://pddimp.yandex.ru/api2/admin/dns/list';
        $method = RequestExecutor::METHOD_GET;
        $parameters = ['domain' => $domain];
        $headers = ['PddToken' => $token];
        $records = HttpRequestWrapper::response($url, $method, $parameters, $headers);

        $arRecords = $records->arBody['records'] ?? [];

        foreach ($arRecords as $arDnsItem) {
            $dnsRecordDto = new DnsRecordDto(...$arDnsItem);
            $result->add($dnsRecordDto);
        }

        return $result;
    }

    public function create(
        string $domain, string $token, string $type, string $subdomain, string $content, ...$any
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
        $rawRecord = HttpRequestWrapper::response($url, $method, $parameters, $headers);

        $rawRecord = $rawRecord->arBody['record'] ?? false;

        if ($rawRecord) {
            $result = new DnsRecordDto(...$rawRecord);
        }

        return $result;
    }

    public function delete(string $domain, string $token, string $subdomain, string $type, ...$any): int
    {
        $records = $this->getAll($domain, $token);
        $recordsForDeleting = $records->filterAnd($subdomain, $type, '');

        foreach ($recordsForDeleting as $recordDto) {
            $url = 'https://pddimp.yandex.ru/api2/admin/dns/del';
            $method = RequestExecutor::METHOD_POST;
            $parameters = ['domain' => $domain, 'record_id' => $recordDto->record_id];
            $headers = ['PddToken' => $token];
            HttpRequestWrapper::response($url, $method, $parameters, $headers);
        }

        return count($recordsForDeleting);
    }
}