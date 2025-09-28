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
use Override;

/**
 * Null implementation of the tile cache
 * This implementation does not cache anything and always recomputes the map
 */
final readonly class NullTileCache implements TileCacheInterface
{
    #[Override]
    public function bounds(Closure $compute): Bounds
    {
        return $compute();
    }

    #[Override]
    public function map(TileMapCoordinates $coordinates, Closure $compute): ?GdImage
    {
        return $compute($coordinates);
    }

    #[Override]
    public function fullSizeTile(int $x, int $y, Closure $compute): GdImage
    {
        return $compute($x, $y);
    }

    #[Override]
    public function tile(int $x, int $y, int $zoom, Closure $compute): GdImage
    {
        return $compute($x, $y, $zoom);
    }

    #[Override]
    public function withNamespace(string $namespace): static
    {
        return $this;
    }
}
