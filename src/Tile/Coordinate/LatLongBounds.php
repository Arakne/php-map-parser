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

use function count;

/**
 * Geographical bounding box using latitude and longitude
 */
final readonly class LatLongBounds
{
    public function __construct(
        public float $west,
        public float $south,
        public float $east,
        public float $north,
    ) {}

    /**
     * Convert CSV string to LatLongBounds
     *
     * @param string $str
     *
     * @return self|null The bounds or null if the string is invalid
     */
    public static function fromString(string $str): ?self
    {
        $parts = explode(',', $str, 4);

        if (count($parts) !== 4) {
            return null;
        }

        return new self(
            west: (float) $parts[0],
            south: (float) $parts[1],
            east: (float) $parts[2],
            north: (float) $parts[3],
        );
    }
}
