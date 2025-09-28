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

namespace Arakne\MapParser\Tile;

/**
 * Store map coordinates and offsets for rendering on a tile
 */
final class TileMapCoordinates
{
    public function __construct(
        /**
         * The X coordinate of the map
         *
         * @psalm-api
         */
        public int $x,

        /**
         * The Y coordinate of the map
         *
         * @psalm-api
         */
        public int $y,

        /**
         * X Offset in pixels of the rendered map on the tile
         */
        public int $xSourceOffset = 0,

        /**
         * Y Offset in pixels of the rendered map on the tile
         */
        public int $ySourceOffset = 0,

        /**
         * X position in pixels on the tile where to draw the map
         */
        public int $xDestinationOffset = 0,

        /**
         * Y position in pixels on the tile where to draw the map
         */
        public int $yDestinationOffset = 0,
    ) {}
}
