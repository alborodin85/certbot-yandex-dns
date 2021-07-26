<?php

namespace It5\SystemDnsShell;

class DnsRecordDto
{
    public string $token = '';

    function __construct(
        public string $record_id,
        public string $domain,
        public string $subdomain,
        public string $fqdn,
        public string $type,
        public string $content,
        public string $priority,
        public string $ttl,
    )
    {
    }

    public function uuid(): string
    {
        $result = $this->subdomain . '_' . $this->type . '_' . $this->content;

        return $result;
    }

    public function toArray(): array
    {
        $thisAsArray = new \ArrayObject($this);
        $result = [];
        foreach ($thisAsArray as $fieldName => $fieldValue) {
            $result[$fieldName] = $fieldValue;
        }

        return $result;
    }
}