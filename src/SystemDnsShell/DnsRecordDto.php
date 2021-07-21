<?php

namespace It5\SystemDnsShell;

class DnsRecordDto
{
    function __construct(
        public string $ttl,
        public string $domain,
        public string $fqdn,
        public string $record_id,
        public string $type,
        public string $subdomain,
        public string $priority,
        public string $content,
    ) { }
}