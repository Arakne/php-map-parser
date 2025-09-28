<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRendererInterface;
use Arakne\MapParser\Renderer\TileRenderer;
use Arakne\MapParser\Tile\BaseTileRenderer;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\Coordinate\CoordinateSystem;
use Arakne\MapParser\Tile\TileMapCoordinates;
use Arakne\MapParser\Tile\TileRendererInterface;
use Closure;
use GdImage;
use Override;

use function imagealphablending;
use function imagesavealpha;
use function max;

/**
 * Combine both world map and game maps on same tiles.
 *
 * This allows to render precise maps when zoomed in, while still having the world map as background when zoomed out
 * or on missing areas.
 */
final readonly class CombinedWorldMapTileRenderer implements TileRendererInterface
{
    /**
     * The scale to apply when rendering game maps
     * Because world map chunks are 15x15 and tiling system force use of power of 2 (so 16x16),
     * we need to scale up the game maps by 16/15 to avoid offsets between world map and game maps.
     */
    public const float GAME_MAP_SCALE = 16 / 15;

    /**
     * @var non-negative-int
     */
    public int $maxZoom;

    /**
     * Bounds of the game maps coordinates
     */
    public Bounds $bounds;

    /**
     * Coordinate system used for game maps
     */
    public CoordinateSystem $coordinate;

    /**
     * @var non-negative-int
     */
    private int $minZoomLevel;

    private TileRendererInterface $worldMapRenderer;
    private TileRendererInterface $gameMapRenderer;

    public function __construct(
        /**
         * The world map to renderer
         */
        private WorldMapInterface $worldMap,

        /**
         * The map renderer to use for rendering each map
         */
        private MapRendererInterface $renderer,

        /**
         * Resolve the map from the [X,Y] coordinates
         *
         * @var Closure(TileMapCoordinates):(MapStructure|null)
         */
        private Closure $mapResolver,

        /**
         * The minimum zoom level to render game maps over the world map
         * If this value is negative or zero, it will be used as relative to maxZoom (so maxZoom + minZoomLevel)
         */
        int $minZoomLevel,

        /**
         * Use to parse resolved maps
         */
        private MapLoader $loader = new MapLoader(),

        /**
         * Cache implementation used on both tile renderers.
         * A namespace will be automatically applied to avoid collisions.
         */
        private TileCacheInterface $cache = new NullTileCache(),
    ) {
        $this->worldMapRenderer = new WorldMapTileRenderer(
            worldMap: $this->worldMap,
            cache: $this->cache->withNamespace('world_map'),
        );

        $this->gameMapRenderer = new TileRenderer(
            renderer: $this->renderer,
            mapResolver: $this->mapResolver,
            bounds: $this->bounds = $this->worldMap->bounds()->toActualMapBound(),
            scale: self::GAME_MAP_SCALE,
            cache: $this->cache->withNamespace('game_map'),
            loader: $this->loader,
        );

        $this->maxZoom = $this->gameMapRenderer->maxZoom;
        $this->coordinate = $this->gameMapRenderer->coordinate;
        $this->minZoomLevel = $minZoomLevel > 0 ? $minZoomLevel : max(0, $this->maxZoom + $minZoomLevel);
    }

    #[Override]
    public function warmup(?Closure $log = null, int $minZoom = 0): void
    {
        $this->worldMapRenderer->warmup($log, $minZoom);
        $this->gameMapRenderer->warmup($log, max($minZoom, $this->minZoomLevel));
    }

    #[Override]
    public function render(int $x, int $y, int $zoom = 0): GdImage
    {
        $img = $this->worldMapRenderer->render($x, $y, $zoom);
        imagesavealpha($img, true);
        imagealphablending($img, true);

        if ($zoom >= $this->minZoomLevel) {
            $gameMapImg = $this->gameMapRenderer->render($x, $y, $zoom);
            imagecopy($img, $gameMapImg, 0, 0, 0, 0, BaseTileRenderer::TILE_SIZE, BaseTileRenderer::TILE_SIZE);
        }

        return $img;
    }

    #[Override]
    public function renderOriginalSize(int $x, int $y): GdImage
    {
        // Game map has higher resolution, so we need to use its max zoom level on the world map too
        $img = $this->worldMapRenderer->render($x, $y, $this->maxZoom);
        imagesavealpha($img, true);
        imagealphablending($img, true);

        $gameMapImg = $this->gameMapRenderer->renderOriginalSize($x, $y);
        imagecopy($img, $gameMapImg, 0, 0, 0, 0, BaseTileRenderer::TILE_SIZE, BaseTileRenderer::TILE_SIZE);

        return $img;
    }
}
