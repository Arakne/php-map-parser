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

namespace Arakne\MapParser\Tile\Coordinate;

use function assert;

/**
 * Store coordinates bounds
 */
final readonly class Bounds
{
    /**
     * Number of maps per world map chunk (15x15)
     */
    public const int WORLD_MAP_CHUNK_SIZE = 15;

    public function __construct(
        public int $xMin,
        public int $xMax,
        public int $yMin,
        public int $yMax,
    ) {
        assert($xMin <= $xMax && $yMin <= $yMax);
    }

    /**
     * Convert world map bounds to actual map coordinates bounds
     * Each chunk of world map is 15x15 maps, so we need to multiply the bounds by 15
     */
    public function toActualMapBound(): self
    {
        return new self(
            $this->xMin * self::WORLD_MAP_CHUNK_SIZE,
            ($this->xMax + 1) * self::WORLD_MAP_CHUNK_SIZE - 1,
            $this->yMin * self::WORLD_MAP_CHUNK_SIZE,
            ($this->yMax + 1) * self::WORLD_MAP_CHUNK_SIZE - 1,
        );
    }
}
