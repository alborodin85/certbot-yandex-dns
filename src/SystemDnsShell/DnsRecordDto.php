<?php

namespace It5\SystemDnsShell;

class DnsRecordDto
{
    function __construct(
        public string $record_id,
        public string $domain,
        public string $subdomain,
        public string $fqdn,
        public string $type,
        public string $content,
        public string $priority,
        public string $ttl,
    ) { }
}