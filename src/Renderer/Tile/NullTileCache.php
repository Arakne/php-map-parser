<?php

namespace Arakne\MapParser\Renderer\Tile;

use Arakne\MapParser\Loader\MapStructure;
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
    public function map(MapStructure $map, Closure $compute): GdImage
    {
        return $compute($map);
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
}
