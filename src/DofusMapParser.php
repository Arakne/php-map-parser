<?php

namespace Arakne\MapParser;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\Layer\LayerRendersBuilder;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\MapRendererInterface;
use Arakne\MapParser\Sprite\Cache\InMemorySpriteCache;
use Arakne\MapParser\Sprite\Cache\SpriteCacheInterface;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\TileMapCoordinates;
use Arakne\MapParser\Tile\TileRendererInterface;
use Arakne\MapParser\WorldMap\CombinedWorldMapTileRenderer;
use Arakne\MapParser\WorldMap\SwfWorldMap;
use Arakne\MapParser\WorldMap\WorldMapTileRenderer;
use Arakne\Swf\SwfFile;
use Closure;
use GdImage;
use InvalidArgumentException;

use function glob;
use function is_int;

/**
 * Facade for parsing and rendering Dofus maps.
 */
final class DofusMapParser
{
    public const int AMAKNA_SUPERAREA_ID = 0;
    public const int INCARNAM_SUPERAREA_ID = 3;

    /**
     * Get ground sprites repository.
     */
    public private(set) SpriteRepositoryInterface $grounds {
        get => $this->grounds ??= new SwfSpriteRepository(
            glob($this->dofusPath . '/clips/gfx/g*.swf') ?: [],
            cache: $this->spriteCache->withNamespace('grounds'),
        );
    }

    /**
     * Get object sprites repository.
     */
    public private(set) SpriteRepositoryInterface $objects {
        get => $this->objects ??= new SwfSpriteRepository(
            glob($this->dofusPath . '/clips/gfx/o*.swf') ?: [],
            cache: $this->spriteCache->withNamespace('objects'),
        );
    }

    public private(set) MapRendererInterface $renderer {
        get {
            if (isset($this->renderer)) {
                return $this->renderer;
            }

            $layers = new LayerRendersBuilder($this->grounds, $this->objects);

            if ($this->layersConfigurator !== null) {
                ($this->layersConfigurator)($layers);
            }

            return $this->renderer = new MapRenderer($layers->build());
        }
    }

    public readonly MapLoader $loader;

    /**
     * @param list<callable(MapStructure):list<object>> $attachmentsProviders List of attachments providers to add to the map.
     */
    public function __construct(
        /**
         * Path to the dofus client directory.
         */
        private readonly string $dofusPath,

        /**
         * Path to the maps SWF files.
         * If null, defaults to "$dofusPath/data/maps".
         */
        private readonly ?string $mapsPath = null,

        /**
         * Closure used to load a map by its coordinates.
         * This value must be set if you want to use world map rendering with actual game maps.
         *
         * It takes as first parameter the map coordinates, and as second parameter super area ID.
         * It can return either:
         * - an integer: the map ID to load
         * - a SwfFile: the SWF file to load
         * - a MapStructure: the pre-loaded map structure
         *
         * @var Closure(MapCoordinates, int):(int|SwfFile|MapStructure|null)|null
         */
        private readonly ?Closure $mapByCoordinates = null,

        /**
         * Cache implementation to use for tile rendering.
         * Only used when rendering world maps.
         */
        private readonly TileCacheInterface $tileCache = new NullTileCache(),

        /**
         * Cache implementation to use for sprites.
         */
        private readonly SpriteCacheInterface $spriteCache = new InMemorySpriteCache(100),

        /**
         * Custom configuration for renderer layers.
         *
         * @var Closure(LayerRendersBuilder):void|null
         */
        private readonly ?Closure $layersConfigurator = null,

        /**
         * List of attachments providers to add to the map.
         * Use this to provides the map key and coordinates automatically.
         *
         * @param list<callable(MapStructure):list<object>> $attachmentsProviders
         * @var list<callable(MapStructure):list<object>> $attachmentsProviders
         */
        array $attachmentsProviders = [],
    ) {
        $this->loader = new MapLoader(attachmentsProviders: $attachmentsProviders);
    }

    /**
     * Render a map to a GD image.
     *
     * Usage:
     * ```php
     * // Render map with ID 1302
     * $img = $parser->render(1302);
     *
     * // Display the image in the browser
     * header('Content-Type: image/png');
     * imagepng($img);
     * ```
     *
     * @param int|SwfFile|MapStructure $map The map to render, either as an ID, a SWF file, or a pre-loaded MapStructure.
     *
     * @return GdImage The rendered map as a GD image.
     * @throws InvalidArgumentException If the map ID is invalid or the SWF file cannot be loaded.
     */
    public function render(int|SwfFile|MapStructure $map): GdImage
    {
        return $this->renderer->render($this->load($map) ?? throw new InvalidArgumentException('Map not found'));
    }

    /**
     * Load and parse a map
     *
     * Usage:
     * ```php
     * $map = $parser->load(1302); // Load map with ID 1302
     *
     * // Now you can access map properties
     * foreach ($map->cells as $cell) {
     *     if ($cell->isTeleport) {
     *         // ...
     *     }
     * }
     * ```
     *
     * @param int|SwfFile|MapStructure $map The map to load, either as an ID, a SWF file, or a pre-loaded MapStructure.
     *
     * @return Map|null The loaded map, or null if the map ID is not found.
     */
    public function load(int|SwfFile|MapStructure $map): ?Map
    {
        if (!$struct = $this->toMapStructure($map)) {
            return null;
        }

        return $this->loader->load($struct);
    }

    /**
     * Get the tile renderer for incarnam world map.
     * If {@see DofusMapParser::$mapByCoordinates} is set, both world map and game maps will be rendered.
     *
     * Usage:
     * ```php
     * $tileRenderer = $parser->incarnamWorldMap(6);
     *
     * header('Content-Type: image/png');
     * imagepng($tileRenderer->render(new TileMapCoordinates($_GET['x'], $_GET['y'], $_GET['z'])));
     * ```
     *
     * @param int $minZoomLevel Zoom level when the actual game maps starts to be renderer over world map.
     *                          If negative or zero, it will be used as relative to maxZoom (so maxZoom + minZoomLevel)
     * @return TileRendererInterface
     */
    public function incarnamWorldMap(int $minZoomLevel = -2): TileRendererInterface
    {
        return $this->worldMap(self::INCARNAM_SUPERAREA_ID, $minZoomLevel);
    }

    /**
     * Get the tile renderer for amakna world map.
     * If {@see DofusMapParser::$mapByCoordinates} is set, both world map and game maps will be rendered.
     *
     *  Usage:
     *  ```php
     *  $tileRenderer = $parser->amaknaWorldMap(7);
     *
     *  header('Content-Type: image/png');
     *  imagepng($tileRenderer->render(new TileMapCoordinates($_GET['x'], $_GET['y'], $_GET['z'])));
     *  ```
     * @param int $minZoomLevel Zoom level when the actual game maps starts to be renderer over world map
     *                          If negative or zero, it will be used as relative to maxZoom (so maxZoom + minZoomLevel)
     *
     * @return TileRendererInterface
     */
    public function amaknaWorldMap(int $minZoomLevel = -2): TileRendererInterface
    {
        return $this->worldMap(self::AMAKNA_SUPERAREA_ID, $minZoomLevel);
    }

    /**
     * Get the tile renderer for the given super area world map.
     * If {@see DofusMapParser::$mapByCoordinates} is set, both world map and game maps will be rendered.
     *
     * @param non-negative-int $superAreaId The super area ID to render (0 = Amakna, 3 = Incarnam, etc.)
     * @param int $minZoomLevel Zoom level when the actual game maps starts to be renderer over world map
     *                          If negative or zero, it will be used as relative to maxZoom (so maxZoom + minZoomLevel)
     *
     * @return TileRendererInterface
     */
    public function worldMap(int $superAreaId, int $minZoomLevel = -2): TileRendererInterface
    {
        $worldMap = new SwfWorldMap(new SwfFile($this->dofusPath . '/clips/maps/' . $superAreaId . '.swf'));

        if ($this->mapByCoordinates === null) {
            return new WorldMapTileRenderer(
                $worldMap,
                cache: $this->tileCache->withNamespace('super_area_' . $superAreaId),
            );
        }

        return new CombinedWorldMapTileRenderer(
            $worldMap,
            $this->renderer,
            function (TileMapCoordinates $coordinates) use ($superAreaId) {
                $ret = ($this->mapByCoordinates)(
                    new MapCoordinates($coordinates->x, $coordinates->y),
                    $superAreaId
                );

                return $ret !== null ? $this->toMapStructure($ret) : null;
            },
            $minZoomLevel,
            $this->loader,
            $this->tileCache->withNamespace('super_area_' . $superAreaId),
        );
    }

    private function toMapStructure(int|SwfFile|MapStructure $map): ?MapStructure
    {
        if (is_int($map)) {
            $filepath = glob(($this->mapsPath ?? $this->dofusPath . '/data/maps') . '/' . $map . '_*.swf')[0] ?? null;

            if ($filepath === null) {
                return null;
            }

            $map = new SwfFile($filepath);
        }

        if ($map instanceof SwfFile) {
            $map = MapStructure::fromSwfFile($map);
        }

        return $map;
    }
}
