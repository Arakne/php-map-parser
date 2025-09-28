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

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Tile\Coordinate\Bounds;

/**
 * Represents a dofus world map file
 * There are located in Dofus/clips/maps directory
 */
interface WorldMapInterface
{
    /**
     * Get the map bounds (min and max coordinates)
     *
     * Note: because each chunk corresponds to 15x15 maps, those coordinates are 1/15th of actual map coordinates
     */
    public function bounds(): Bounds;

    /**
     * Get the chunk image data (PNG) at given coordinates
     * If the chunk does not exist, null is returned
     *
     * The returned image must have same dimensions as maps:
     * - width: {@see MapRenderer::DISPLAY_WIDTH}
     * - height: {@see MapRenderer::DISPLAY_HEIGHT}
     *
     * @param int $x
     * @param int $y
     *
     * @return string|null
     */
    public function chunk(int $x, int $y): ?string;
}
