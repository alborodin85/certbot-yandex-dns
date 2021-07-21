<?php

namespace It5\SystemDnsShell;

class SystemDnsShell
{
    public function waitingSomeParameters(DnsRecordsCollection $dnsRecordsCollection)
    {

    }
    public function waitingOneParameter(DnsRecordDto $recordDto): bool
    {

        return true;
    }

    public function getDnsParameter(): string
    {
        $commandResult = `dig s-dver.ru txt +noall +answer @8.8.8.8`;

        return '';
    }
}