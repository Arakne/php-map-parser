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

namespace Arakne\MapParser\Tile\Cache;

use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\TileMapCoordinates;
use Closure;
use GdImage;

/**
 * Cache for rendered maps and tiles
 */
interface TileCacheInterface
{
    /**
     * Get (or compute and store) the map bounds
     * If the bounds are passed as parameter on the tile renderer, this method will not be called.
     *
     * @param Closure():Bounds $compute The function to compute the bounds if not cached
     * @return Bounds
     */
    public function bounds(Closure $compute): Bounds;

    /**
     * Get (or compute and store) the rendered map image
     *
     * @param TileMapCoordinates $coordinates
     * @param Closure(TileMapCoordinates):(GdImage|null) $compute
     *
     * @return GdImage|null
     */
    public function map(TileMapCoordinates $coordinates, Closure $compute): ?GdImage;

    /**
     * Get (or compute and store) the rendered full-size tile image
     *
     * @param non-negative-int $x The X coordinate of the tile
     * @param non-negative-int $y The Y coordinate of the tile
     * @param Closure(non-negative-int, non-negative-int):GdImage $compute The function to compute the tile if not cached. Takes $x, $y as parameters
     *
     * @return GdImage
     */
    public function fullSizeTile(int $x, int $y, Closure $compute): GdImage;

    /**
     * Get (or compute and store) the tile image
     *
     * @param non-negative-int $x The X coordinate of the tile
     * @param non-negative-int $y The Y coordinate of the tile
     * @param non-negative-int $zoom The zoom level of the tile
     * @param Closure(non-negative-int, non-negative-int, non-negative-int):GdImage $compute The function to compute the tile if not cached.
     *
     * @return GdImage
     */
    public function tile(int $x, int $y, int $zoom, Closure $compute): GdImage;

    /**
     * Create a new instance with the given namespace for caching
     * This allows to separate caches for different map sets / tile renderers, while using the same cache backend.
     *
     * @param string $namespace The namespace key
     *
     * @return static The new cache instance
     */
    public function withNamespace(string $namespace): static;
}
