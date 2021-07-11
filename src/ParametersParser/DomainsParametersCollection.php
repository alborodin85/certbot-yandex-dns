<?php

namespace It5\ParametersParser;

use JetBrains\PhpStorm\Pure;
use It5\Localization\Ru;

class DomainsParametersCollection implements \Iterator, \ArrayAccess, \Countable
{
    private int $index;
    private array $collection;

    public function __construct()
    {
        $this->collection = [];
        $this->rewind();
    }

    public function add(DomainParametersDto $item): void
    {
        $this->collection[] = $item;
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    #[Pure]
    public function current(): DomainParametersDto
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

    public function offsetGet($offset): DomainParametersDto
    {
        return $this->collection[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof DomainParametersDto)) {
            throw new DomainsParametersError(Ru::get('errors.domain_coll_incorrect_value'));
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
}