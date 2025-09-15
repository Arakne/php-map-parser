<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Tile\BaseTileRenderer;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\MapCoordinates;
use GdImage;

use function assert;
use function imagecreatefromstring;
use function imagesavealpha;

/**
 * Render dofus world maps as square tiles compatible with leaflet or other tile-based map viewers.
 */
final class WorldMapTileRenderer extends BaseTileRenderer
{
    /**
     * @param WorldMapInterface $worldMap
     * @param positive-int $tileSize {@see BaseTileRenderer::$tileSize}
     * @param TileCacheInterface $cache The cache to use for storing rendered chunks and tiles
     */
    public function __construct(
        /**
         * The world map to render
         */
        private readonly WorldMapInterface $worldMap,
        int $tileSize = self::TILE_SIZE,
        TileCacheInterface $cache = new NullTileCache(),
    ) {
        parent::__construct(
            $this->renderChunk(...),
            $worldMap->bounds(),
            mapWidth: MapRenderer::DISPLAY_WIDTH,
            mapHeight: MapRenderer::DISPLAY_HEIGHT,
            tileSize: $tileSize,
            cache: $cache,
        );
    }

    private function renderChunk(MapCoordinates $coordinates): ?GdImage
    {
        if (!$map = $this->worldMap->chunk($coordinates->x, $coordinates->y)) {
            return null;
        }

        $img = imagecreatefromstring($map);
        assert($img !== false);

        imagesavealpha($img, true);

        return $img;
    }
}
