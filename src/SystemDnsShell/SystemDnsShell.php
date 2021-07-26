<?php

namespace It5\SystemDnsShell;

use It5\DebugLibs\DebugLib;
use JetBrains\PhpStorm\Pure;

class SystemDnsShell
{
    private CliCommandExecutor $commandExecutor;

    #[Pure] public function __construct(
        //
    ) {
        $this->commandExecutor = new CliCommandExecutor();
    }

    public function setCommandExecutorMock(CliCommandExecutor $commandExecutor)
    {
        $this->commandExecutor = $commandExecutor;
    }

    public function getDnsParameterValues(
        string $domain,
        string $subdomain,
        string $type,
        string $dnsServerIp = ''
    ): DnsRecordsCollection {
        $recordsCollection = new DnsRecordsCollection();
        $fullDomain = $domain;
        if ($subdomain != '@') {
            $fullDomain = $subdomain . '.' . $domain . '.';
        }
        $type = mb_strtolower($type);
        $command = "dig +nocmd {$fullDomain} {$type} +noall +answer";
        if ($dnsServerIp) {
            $command .= " @{$dnsServerIp}";
        }
        $commandResult = $this->commandExecutor->getCommandResultArray($command, '');

        $recordId = 1;
        foreach ($commandResult as $strRecord) {
            $spaceSubs = '##SP##';
            $matches = [];
            $quotesResult = preg_match('/\"(.*?)\"/miu', $strRecord, $matches);
            if ($quotesResult) {
                $content = preg_replace('/\s/', $spaceSubs, $matches[1]);
                $strRecord = str_replace('"' . $matches[1] . '"', $content, $strRecord);
            }

            $arRecord = preg_split('/\s/', $strRecord);
            $arRecord = array_filter($arRecord, fn($item) => !!$item);
            $arRecord = array_values($arRecord);
            $content = $arRecord[4];
            $content = str_replace($spaceSubs, " ", $content);

            $recordObj = new DnsRecordDto(
                record_id: $recordId,
                domain: $domain,
                subdomain: $subdomain,
                fqdn: $fullDomain,
                type: trim($arRecord[3]),
                content: $content,
                priority: 1,
                ttl: trim($arRecord[2]),
            );
            $recordsCollection[] = $recordObj;
            $recordId++;
        }

        return $recordsCollection;
    }
}