<?php

/*
 * This file is part of PHP Map parser.
 *
 * PHP Map parser is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * PHP Map parser is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with PHP Map parser.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2019-2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

namespace Arakne\MapParser\Util;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Override;
use Traversable;

use function array_fill;
use function count;

/**
 * Simple in-memory cache using the clock algorithm for eviction.
 *
 * @template K of array-key
 * @template V
 *
 * @implements ArrayAccess<K, V>
 * @implements IteratorAggregate<K, V>
 */
final class ClockCache implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Maps keys to cache items
     *
     * @var array<K, ClockCacheItem<K, V>>
     */
    private array $values = [];

    /**
     * Fixed size array of slots, each containing either null or a reference to a cache item.
     *
     * @var list<ClockCacheItem<K, V>|null>
     */
    private array $slots = [];

    /**
     * Current position of the "hand" in the clock algorithm
     * This value is always a valid index in the $slots array.
     *
     * @var non-negative-int
     */
    private int $hand = 0;

    public function __construct(
        /**
         * Maximum number of items in the cache
         *
         * @var positive-int
         */
        public readonly int $capacity,
    ) {
        $this->slots = array_fill(0, $capacity, null);
    }

    #[Override]
    public function count(): int
    {
        return count($this->values);
    }

    #[Override]
    public function getIterator(): Traversable
    {
        foreach ($this->values as $key => $item) {
            yield $key => $item->value;
        }
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        $value = $this->values[$offset] ?? null;

        if ($value === null) {
            return null;
        }

        $value->used = true;
        return $value->value;
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $offset !== null or throw new InvalidArgumentException('Key cannot be null (append array syntax is not supported)');

        // Update existing value
        if ($oldValue = $this->values[$offset] ?? null) {
            $oldValue->value = $value;
            $oldValue->used = true;
            return;
        }

        $slot = $this->evict();
        $item = new ClockCacheItem($offset, $slot, $value, true);

        $this->values[$offset] = $item;
        // @phpstan-ignore assign.propertyType
        $this->slots[$slot] = $item;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $value = $this->values[$offset] ?? null;

        if ($value === null) {
            return;
        }

        unset($this->values[$offset]);
        // @phpstan-ignore assign.propertyType
        $this->slots[$value->slot] = null;
        $this->hand = $value->slot;
    }

    /**
     * Free a slot if needed using the clock algorithm, and return the freed slot index.
     *
     * @return non-negative-int The slot that was freed
     */
    private function evict(): int
    {
        $capacity = $this->capacity;
        $slot = $this->hand;

        for (;;) {
            $item = $this->slots[$slot];

            if ($item === null) {
                // Empty slot, use it
                break;
            }

            if ($item->used) {
                // Second chance
                $item->used = false;
                ++$slot;

                if ($slot >= $capacity) {
                    $slot = 0;
                }

                continue;
            }

            // Evict this item
            unset($this->values[$item->key]);
            // @phpstan-ignore assign.propertyType
            $this->slots[$slot] = null;
            break;
        }

        $hand = $slot + 1;
        $this->hand = $hand >= $capacity ? 0 : $hand;

        return $slot;
    }
}

/**
 * @internal
 * @template K of array-key
 * @template V
 */
final class ClockCacheItem
{
    public function __construct(
        /** @var K */
        public readonly string|int $key,
        /** @var non-negative-int */
        public readonly int $slot,
        /** @var V */
        public mixed $value,
        public bool $used = false,
    ) {}
}
