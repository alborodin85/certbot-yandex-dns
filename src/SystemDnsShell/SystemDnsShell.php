<?php

namespace It5\SystemDnsShell;

use It5\DebugLibs\DebugLib;
use JetBrains\PhpStorm\Pure;

class SystemDnsShell
{
    private CliCommandExecutor $commandExecutor;

    #[Pure] public function __construct(
        private int $maxWaitingSpreadingSeconds,
        private int $testingSpreadingIntervalSeconds,
        private string $dnsServerIp,
    ) {
        $this->commandExecutor = new CliCommandExecutor();
    }

    public function setCommandExecutorMock(CliCommandExecutor $commandExecutor)
    {
        $this->commandExecutor = $commandExecutor;
    }

    public function waitingSomeParameters(DnsRecordsCollection $dnsRecordsCollection)
    {

    }
    public function waitingOneParameter(DnsRecordDto $recordDto): bool
    {
        $result = false;
        $startTime = time();

        $currentParameterValue = '';

        while ($currentParameterValue != $recordDto->content) {
            if (time() > $startTime + $this->maxWaitingSpreadingSeconds) {
                break;
            }
            $recordsCollection = $this->getDnsParameterValues(
                $recordDto->domain,
                $recordDto->subdomain,
                $recordDto->type,
                $this->dnsServerIp
            );
            $correctRecords = $recordsCollection->filterAnd(
                $recordDto->subdomain,
                $recordDto->type,
                $recordDto->content,
            );
            if (count($correctRecords)) {
                $result = true;
                break;
            }
            sleep($this->testingSpreadingIntervalSeconds);
        }

        return $result;
    }

    public function getDnsParameterValues(
        string $domain,
        string $subdomain,
        string $type,
        string $dnsServerIp = ''
    ): DnsRecordsCollection {
        $recordsCollection = new DnsRecordsCollection();
        $fullDomain = $subdomain . '.' . $domain . '.';
        $type = mb_strtolower($type);
        $command = "dig {$fullDomain} {$type} +noall +answer";
        if ($dnsServerIp) {
            $command .= " @{$dnsServerIp}";
        }
        $commandResult = $this->commandExecutor->getCommandResultArray($command, '');

        $recordId = 1;
        foreach ($commandResult as $strRecord) {
            $arRecord = preg_split('/\s/', $strRecord);
            DebugLib::dump($arRecord);
            $recordObj = new DnsRecordDto(
                record_id: $recordId,
                domain: $domain,
                subdomain: $subdomain,
                fqdn: $fullDomain,
                type: trim($arRecord[3]),
                content: trim(str_replace('"', '', $arRecord[4])),
                priority: 1,
                ttl: trim($arRecord[2]),
            );
            $recordsCollection[] = $recordObj;
            $recordId++;
        }

        return $recordsCollection;
    }
}