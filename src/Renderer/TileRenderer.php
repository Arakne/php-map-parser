<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Tile\BaseTileRenderer;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\MapCoordinates;
use Arakne\MapParser\Util\Bounds;
use Closure;
use GdImage;

/**
 * Render dofus maps as square tiles compatible with leaflet or other tile-based map viewers.
 *
 * @psalm-api
 */
final class TileRenderer extends BaseTileRenderer
{
    /**
     * @param Bounds $bounds The world map coordinates bounds
     * @param float $scale The scale to apply to each map (default: 1.0)
     * @param positive-int $tileSize The tile size in pixels (default: 256)
     * @param TileCacheInterface $cache The cache to use for storing rendered maps and tiles
     */
    public function __construct(
        /**
         * The map renderer to use for rendering each map
         */
        private readonly MapRendererInterface $renderer,

        /**
         * Resolve the map from the [X,Y] coordinates
         *
         * @var Closure(MapCoordinates):(MapStructure|null)
         */
        private readonly Closure $mapResolver,
        Bounds $bounds,
        float $scale = 1.0,
        int $tileSize = self::TILE_SIZE,
        TileCacheInterface $cache = new NullTileCache(),
        private readonly MapLoader $loader = new MapLoader(),
    ) {
        parent::__construct(
            $this->doRenderMap(...),
            $bounds,
            $scale,
            MapRenderer::DISPLAY_WIDTH,
            MapRenderer::DISPLAY_HEIGHT,
            $tileSize,
            $cache,
        );
    }

    private function doRenderMap(MapCoordinates $coordinates): ?GdImage
    {
        if (!$map = ($this->mapResolver)($coordinates)) {
            return null;
        }

        $map = $this->loader->load($map);

        return $this->renderer->render($map);
    }
}
