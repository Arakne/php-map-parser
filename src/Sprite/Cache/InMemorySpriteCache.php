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

namespace Arakne\MapParser\Sprite\Cache;

use Arakne\MapParser\Sprite\Sprite;
use Arakne\MapParser\Util\ClockCache;
use Closure;
use Override;

/**
 * Simple in-memory implementation of {@see SpriteCacheInterface} using a clock cache for sprites.
 */
final class InMemorySpriteCache implements SpriteCacheInterface
{
    /**
     * @var array<int, string>|null
     */
    private ?array $exports = null;

    /**
     * @var ClockCache<int, Sprite>
     */
    private ClockCache $cache;

    /**
     * @param positive-int $capacity Maximum number of sprites to keep in cache
     */
    public function __construct(int $capacity = 100)
    {
        $this->cache = new ClockCache($capacity);
    }

    #[Override]
    public function exports(Closure $compute): array
    {
        return $this->exports ??= $compute();
    }

    #[Override]
    public function sprite(int $id, Closure $compute): Sprite
    {
        return $this->cache[$id] ??= $compute($id);
    }

    #[Override]
    public function withNamespace(string $namespace): static
    {
        return new self();
    }
}
