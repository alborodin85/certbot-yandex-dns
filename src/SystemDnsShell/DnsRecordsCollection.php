<?php

namespace It5\SystemDnsShell;

use JetBrains\PhpStorm\Pure;
use function PHPUnit\Framework\returnValue;

class DnsRecordsCollection implements \Iterator, \ArrayAccess, \Countable
{
    private int $index;
    private array $collection;

    public function __construct(DnsRecordDto ...$items)
    {
        $this->collection = $items;
        $this->rewind();
    }

    public function add(DnsRecordDto ...$items): void
    {
        $this->collection = array_merge($this->collection, $items);
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
            throw new DnsRecordError('Некорректный тип элемента DnsRecordsCollection!');
        }
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
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

    public function fromArray(array $arItems): self
    {
        $this->collection = [];
        foreach ($arItems as $item) {
            $this[] = $item;
        }

        return $this;
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

        $result = $this->fromArray($arRecords);

        return $result;
    }
}