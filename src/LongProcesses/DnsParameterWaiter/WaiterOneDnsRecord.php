<?php

namespace It5\LongProcesses\DnsParameterWaiter;

use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\SystemDnsShell;
use JetBrains\PhpStorm\Pure;

class WaiterOneDnsRecord
{
    private SystemDnsShell $dnsShell;

    #[Pure] public function __construct(
        private int $maxWaitingSpreadingSeconds,
        private int $testingSpreadingIntervalSeconds,
        private string $dnsServerIp,
    )
    {
        $this->dnsShell = new SystemDnsShell();
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
            $recordsCollection = $this->dnsShell->getDnsParameterValues(
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
}