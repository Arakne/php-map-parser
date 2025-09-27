<?php

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
