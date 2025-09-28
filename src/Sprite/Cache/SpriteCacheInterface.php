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
use Closure;

interface SpriteCacheInterface
{
    /**
     * Map of exported sprite ID to SWF file path
     *
     * @param Closure():array<int, string> $compute Function to compute the exports if not already cached
     *
     * @return array<int, string>
     */
    public function exports(Closure $compute): array;

    /**
     * Get a sprite from the cache
     *
     * @param int $id Sprite ID
     * @param Closure(int):Sprite $compute Function to compute the sprite if not already cached. Takes the sprite ID as argument.
     *
     * @return Sprite
     */
    public function sprite(int $id, Closure $compute): Sprite;

    /**
     * Create a new instance with the given namespace for caching
     * This allows to separate caches for grounds and objects, while using the same cache backend.
     *
     * @param string $namespace The namespace key
     *
     * @return static The new cache instance
     */
    public function withNamespace(string $namespace): static;
}
