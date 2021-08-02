<?php

namespace It5\LongProcesses\DnsParameterWaiter;

use It5\DebugLibs\DebugLib;
use It5\Localization\Trans;
use It5\SystemDnsShell\DnsRecordDto;
use It5\SystemDnsShell\DnsRecordsCollection;
use It5\SystemDnsShell\SystemDnsShell;
use JetBrains\PhpStorm\Pure;

class WaiterSomeDnsRecords
{
    private SystemDnsShell $dnsShell;

    public function __construct(
        private int    $maxWaitingSpreadingSeconds,
        private int    $testingSpreadingIntervalSeconds,
        private string $dnsServerIp,
    )
    {
        Trans::instance()->addPhrases(__DIR__ . '/localization/ru.php');
        $this->dnsShell = new SystemDnsShell();
    }

    public function additionWaiting(int $additionWaitingSecs)
    {
        sleep($additionWaitingSecs);
    }

    public function waitingSomeParameters(DnsRecordsCollection $dnsRecordsCollection): bool
    {
        if (!count($dnsRecordsCollection)) {
            return false;
        }
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
                if ($arParamsResults[$this->recordUuid($recordDto)]) {
                    continue;
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
                    DebugLib::printAndLog(Trans::T('record_appeared', $recordDto->subdomain, $recordDto->content));
                    $arParamsResults[$this->recordUuid($recordDto)] = true;
                }
            }

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
        $result = $recordDto->subdomain . '_' . $recordDto->type . '_' . $recordDto->content;

        return $result;
    }
}
