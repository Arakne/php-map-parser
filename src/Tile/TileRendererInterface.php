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

use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\Coordinate\CoordinateSystem;
use Closure;
use GdImage;

/**
 * Render square tiles compatible with leaflet or other tile-based map viewers from rectangular map sets.
 */
interface TileRendererInterface
{
    /**
     * The maximum zoom level
     * This value is log2($size)
     *
     * @var non-negative-int
     */
    public int $maxZoom { get; }

    /**
     * Bounds of the map coordinates
     */
    public Bounds $bounds { get; }

    /**
     * Get the coordinate system used for maps
     */
    public CoordinateSystem $coordinate { get; }

    /**
     * Warmup the tile cache by pre-rendering all tiles up to the given maximum zoom level
     *
     * @param Closure(string, non-negative-int, positive-int):void|null $log A logging function receiving the built level, current tile number, and total tiles to build
     * @param non-negative-int $minZoom The minimum zoom level to render (default: 0)
     *
     * @return void
     */
    public function warmup(?Closure $log = null, int $minZoom = 0): void;

    /**
     * Render a single tile at the given [X,Y] coordinates
     * Coordinates are in tile space, not map space
     *
     * @param non-negative-int $x The tile X coordinate
     * @param non-negative-int $y The tile Y coordinate
     * @param non-negative-int $zoom The zoom level (0 = normal, 1 = 4x zoom, 2 = 16x zoom, etc.)
     *
     * @return GdImage
     */
    public function render(int $x, int $y, int $zoom = 0): GdImage;

    /**
     * Render a single tile at the given [X,Y] coordinates with the maximum detail (i.e. max zoom)
     * Coordinates are in tile space, not map space
     *
     * @param non-negative-int $x
     * @param non-negative-int $y
     *
     * @return GdImage
     */
    public function renderOriginalSize(int $x, int $y): GdImage;
}
