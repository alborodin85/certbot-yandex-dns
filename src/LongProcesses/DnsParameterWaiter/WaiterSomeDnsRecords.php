<?php

namespace It5\LongProcesses\DnsParameterWaiter;

use It5\DebugLibs\DebugLib;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\SystemDnsShell;
use JetBrains\PhpStorm\Pure;

class WaiterSomeDnsRecords
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

    public function waitingSomeParameters(DnsRecordsCollection $dnsRecordsCollection)
    {
        $result = false;
        $startTime = time();

        $isAllParamPresent = null;
        $arParamsResults = [];
        foreach ($dnsRecordsCollection as $recordDto) {
            $arParamsResults[$this->recordUuid($recordDto)] = false;
        }
        while (is_null($isAllParamPresent)) {
            if (time() > $startTime + $this->maxWaitingSpreadingSeconds) {
                break;
            }
            foreach ($dnsRecordsCollection as $recordDto) {
                DebugLib::dump($this->recordUuid($recordDto));
                if ($arParamsResults[$this->recordUuid($recordDto)]) {
                    continue;
                }
                $recordsCollection = $this->dnsShell->getDnsParameterValues(
                    $recordDto->domain,
                    $recordDto->subdomain,
                    $recordDto->type,
                    $this->dnsServerIp
                );

                DebugLib::dump($recordsCollection);

                $correctRecords = $recordsCollection->filterAnd(
                    $recordDto->subdomain,
                    $recordDto->type,
                    $recordDto->content,
                );

                DebugLib::dump($correctRecords);

                if (count($correctRecords)) {
                    $arParamsResults[$this->recordUuid($recordDto)] = true;
                }
            }

            DebugLib::dump($arParamsResults);

            if (!in_array(false, $arParamsResults)) {
                $result = true;
                break;
            }
            sleep($this->testingSpreadingIntervalSeconds);
        }

        return $result;
    }

    private function recordUuid(DnsRecordDto $recordDto): string
    {
        $result = $recordDto->subdomain . '_' . $recordDto->type . '_' .$recordDto->content;

        return $result;
    }
}