<?php

namespace It5\SystemDnsShell;

use It5\Localization\Trans;
use JetBrains\PhpStorm\Pure;

class DnsRecordsCollection implements \Iterator, \ArrayAccess, \Countable
{
    private int $index;
    private array $collection = [];

    public function __construct(DnsRecordDto ...$items)
    {
        Trans::instance()->addPhrases(__DIR__ . '/localization/ru.php');
        $this->addSomeWithCheckUnique($items);
        $this->rewind();
    }

    public function add(DnsRecordDto ...$items): void
    {
        $this->addSomeWithCheckUnique($items);
    }

    private function addSomeWithCheckUnique(array $arRecords)
    {
        foreach ($arRecords as $recordDto) {
            if ($this->findByUuid($recordDto->uuid())) {
                continue;
            }
            $this->collection[] = $recordDto;
        }
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    #[Pure]
    public function current(): DnsRecordDto
    {
        return $this->collection[$this->key()];
    }

    public function key(): int
    {
        return $this->index;
    }

    public function next(): void
    {
        ++$this->index;
    }

    public function valid(): bool
    {
        return isset($this->collection[$this->index]);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->collection[$offset]);
    }

    public function offsetGet($offset): DnsRecordDto
    {
        return $this->collection[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof DnsRecordDto)) {
            throw new DnsRecordError(Trans::T('errors.invalid_type_for_collection'));
        }
        if (is_null($offset)) {
            if ($this->findByUuid($value->uuid())) {
                return;
            }
            $this->collection[] = $value;
        } else {
            if ($this->findByUuid($value->uuid())) {
                throw new DnsRecordError('errors.adding_duplicate_to_collection', $offset);
            }
            $this->collection[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->collection[$offset]);
    }

    public function count(): int
    {
        return count($this->collection);
    }

    public function toArray(): array
    {
        return $this->collection;
    }

    public function filterAnd(string $subdomain, string $type, string $content): self
    {
        $arRecords = $this->toArray();
        if ($subdomain) {
            $arRecords = array_filter($arRecords, fn(DnsRecordDto $record) => $record->subdomain == $subdomain);
        }
        if ($type) {
            $arRecords = array_filter($arRecords, fn(DnsRecordDto $record) => $record->type == $type);
        }
        if ($content) {
            $arRecords = array_filter($arRecords, fn(DnsRecordDto $record) => $record->content == $content);
        }

        $result = new self(...$arRecords);

        return $result;
    }

    public function findByUuid(string $uuid): DnsRecordDto | null
    {
        $foundItems = array_filter($this->collection, fn ($recordDto) => $recordDto->uuid() == $uuid);
        $result = reset($foundItems) ?: null;

        return $result;
    }

    public function getValuesContent(): array
    {
        $result = [];
        foreach ($this->collection as $recordDto) {
            $result[$recordDto->record_id] = $recordDto->content;
        }

        return $result;
    }
}
