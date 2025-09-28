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

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\Coordinate\CoordinateSystem;
use Closure;
use GdImage;
use Override;

use function assert;
use function ceil;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecopy;
use function imagecopyresampled;
use function imagecreatetruecolor;
use function imagefill;
use function imagesavealpha;
use function imagescale;
use function log;
use function max;

/**
 * Render square tiles compatible with leaflet or other tile-based map viewers from rectangular map sets.
 */
class BaseTileRenderer implements TileRendererInterface
{
    public const int TILE_SIZE = 256;

    /**
     * The size of a size of the complete map in tiles count
     * This value will be rounded to the next power of 2
     *
     * @var positive-int
     */
    private readonly int $size;

    /**
     * The maximum zoom level
     * This value is log2($size)
     *
     * @var non-negative-int
     */
    public readonly int $maxZoom;

    /**
     * Get the coordinate system used for maps
     */
    public readonly CoordinateSystem $coordinate;

    public function __construct(
        /**
         * Resolve the map from the [X,Y] coordinates
         * and render it to a GD image
         *
         * Note: the map should have alpha channel enabled for proper rendering,
         *       so you should call `imagesavealpha($img, true)` before returning the image.
         *
         * @var Closure(TileMapCoordinates):(GdImage|null)
         */
        private readonly Closure $mapRenderer,

        /**
         * The world map coordinates bounds
         */
        public readonly Bounds $bounds,

        /**
         * The scale to apply to each map (default: 1.0)
         *
         * Values higher than 1.0 will upscale the map, values lower than 1.0 will downscale the map.
         * Values lower than 1.0 are not fully supported, as they can cause rendering issues.
         *
         * This value is primarily use to compensate the scale difference between dofus maps and world maps:
         * World map area are 15x15 maps, but tiling force to the next power of 2, which is 16x16,
         * so a scale of 16/15 is required to make the world map tiles align with the area maps.
         *
         * @var float
         */
        private readonly float $scale = 1.0,

        /**
         * The width in pixel of each rendered map
         *
         * @var positive-int
         */
        private readonly int $mapWidth = MapRenderer::DISPLAY_WIDTH,

        /**
         * The height in pixel of each rendered map
         *
         * @var positive-int
         */
        private readonly int $mapHeight = MapRenderer::DISPLAY_HEIGHT,

        /**
         * The tile size in pixels (default: 256)
         * This value should be a power of 2, so it can be evenly divided at each zoom level
         *
         * This value is used for both width and height
         *
         * @var positive-int
         */
        private readonly int $tileSize = self::TILE_SIZE,

        /**
         * The cache to use for storing rendered maps and tiles
         */
        private readonly TileCacheInterface $cache = new NullTileCache(),
    ) {
        $this->size = self::computeSize($bounds, $tileSize, $mapWidth, $mapHeight, $scale);
        $maxZoom = (int) log($this->size, 2);
        assert($maxZoom >= 0);
        $this->maxZoom = $maxZoom;

        $this->coordinate = new CoordinateSystem(
            $bounds,
            $tileSize,
            $maxZoom,
            $mapWidth,
            $mapHeight,
            $scale,
        );
    }

    /**
     * Convert tile coordinates to map coordinates
     * Because tiles can overlap multiple maps, this function can return multiple map coordinates
     *
     * @param non-negative-int $x
     * @param non-negative-int $y
     *
     * @return TileMapCoordinates[]
     */
    final public function toMapCoordinates(int $x, int $y): array
    {
        $xMin = $this->bounds->xMin;
        $xMax = $this->bounds->xMax;
        $yMin = $this->bounds->yMin;
        $yMax = $this->bounds->yMax;

        $scaledWidth = $this->mapWidth * $this->scale;
        $scaledHeight = $this->mapHeight * $this->scale;

        $mapX = (int) ($x * $this->tileSize / $scaledWidth);
        $mapY = (int) ($y * $this->tileSize / $scaledHeight);

        if ($mapX + $xMin > $xMax || $mapY + $yMin > $yMax) {
            return [];
        }

        $Xoffset = (int) (($x * $this->tileSize) - ($mapX * $scaledWidth));
        $Yoffset = (int) (($y * $this->tileSize) - ($mapY * $scaledHeight));

        $map = new TileMapCoordinates($mapX + $xMin, $mapY + $yMin, $Xoffset, $Yoffset);

        $maps = [$map];

        $hasX = ($x + 1) * $this->tileSize > ($mapX + 1) * $scaledWidth && $mapX + $xMin < $xMax;
        $hasY = ($y + 1) * $this->tileSize > ($mapY + 1) * $scaledHeight && $mapY + $yMin < $yMax;

        if ($hasX) {
            $maps[] = new TileMapCoordinates($mapX + $xMin + 1, $mapY + $yMin, 0, $Yoffset, (int) ($scaledWidth - $Xoffset), 0);
        }

        if ($hasY) {
            $maps[] = new TileMapCoordinates($mapX + $xMin, $mapY + $yMin + 1, $Xoffset, 0, 0, (int) ($scaledHeight - $Yoffset));
        }

        if ($hasX && $hasY) {
            $maps[] = new TileMapCoordinates($mapX + $xMin + 1, $mapY + $yMin + 1, 0, 0, (int) ($scaledWidth - $Xoffset), (int) ($scaledHeight - $Yoffset));
        }

        return $maps;
    }

    #[Override]
    public function warmup(?Closure $log = null, int $minZoom = 0): void
    {
        $currentMapCount = 0;
        $totalMapCount = ($this->bounds->xMax - $this->bounds->xMin + 1) * ($this->bounds->yMax - $this->bounds->yMin + 1);
        assert($totalMapCount >= 1);

        for ($x = $this->bounds->xMin; $x <= $this->bounds->xMax; ++$x) {
            for ($y = $this->bounds->yMin; $y <= $this->bounds->yMax; ++$y) {
                if ($log) {
                    $log('maps', ++$currentMapCount, $totalMapCount);
                }

                $this->cache->map(new TileMapCoordinates($x, $y), $this->mapRenderer);
            }
        }

        $tileCount = $this->size;
        $totalTiles = $tileCount * $tileCount;
        $currentTile = 0;

        for ($x = 0; $x < $tileCount; ++$x) {
            for ($y = 0; $y < $tileCount; ++$y) {
                if ($log) {
                    $log('tiles', ++$currentTile, $totalTiles);
                }

                $this->renderOriginalSize($x, $y);
            }
        }

        for ($z = $minZoom; $z < $this->maxZoom; ++$z) {
            $tileCount = (int) (2 ** $z);
            $totalTiles = $tileCount * $tileCount;
            $currentTile = 0;

            for ($x = 0; $x < $tileCount; ++$x) {
                for ($y = 0; $y < $tileCount; ++$y) {
                    if ($log) {
                        $log("zoom {$z}", ++$currentTile, $totalTiles);
                    }

                    $this->render($x, $y, $z);
                }
            }
        }
    }

    #[Override]
    final public function render(int $x, int $y, int $zoom = 0): GdImage
    {
        if ($zoom > $this->maxZoom) {
            return $this->renderUpscaled($x, $y, $zoom);
        }

        if ($zoom === $this->maxZoom) {
            return $this->renderOriginalSize($x, $y);
        }

        return $this->cache->tile($x, $y, $zoom, $this->renderDownscaled(...));
    }

    #[Override]
    final public function renderOriginalSize(int $x, int $y): GdImage
    {
        return $this->cache->fullSizeTile($x, $y, $this->doRenderOriginalSize(...));
    }

    /**
     * @param non-negative-int $x
     * @param non-negative-int $y
     * @param non-negative-int $zoom
     *
     * @return GdImage
     */
    private function renderUpscaled(int $x, int $y, int $zoom): GdImage
    {
        $factor = (int) (2 ** ($zoom - $this->maxZoom));
        assert($factor > 0);

        $tileX = (int) ($x / $factor);
        $tileY = (int) ($y / $factor);

        assert($tileX >= 0 && $tileY >= 0);

        $img = $this->emptyTile();
        $tile = $this->renderOriginalSize($tileX, $tileY);

        imagecopyresampled(
            $img,
            $tile,
            0,
            0,
            (int) (($x % $factor) * ($this->tileSize / $factor)),
            (int) (($y % $factor) * ($this->tileSize / $factor)),
            $this->tileSize,
            $this->tileSize,
            (int) ($this->tileSize / $factor),
            (int) ($this->tileSize / $factor),
        );

        return $img;
    }

    /**
     * @param non-negative-int $x
     * @param non-negative-int $y
     * @param non-negative-int $zoom
     *
     * @return GdImage
     */
    private function renderDownscaled(int $x, int $y, int $zoom): GdImage
    {
        $tileCount = $this->size >> $zoom;
        assert($tileCount > 0);

        $startX = (int) ($x * $tileCount);
        $startY = (int) ($y * $tileCount);

        $img = $this->emptyTile();
        $subtileSize = $this->tileSize / $tileCount;

        for ($x = 0; $x < $tileCount; ++$x) {
            for ($y = 0; $y < $tileCount; ++$y) {
                $gd = $this->renderOriginalSize($startX + $x, $startY + $y);

                imagecopyresampled($img, $gd, (int) ($x * $subtileSize), (int) ($y * $subtileSize), 0, 0, (int) $subtileSize, (int) $subtileSize, $this->tileSize, $this->tileSize);
            }
        }

        return $img;
    }

    private function renderMap(TileMapCoordinates $coordinates): ?GdImage
    {
        $img = $this->cache->map($coordinates, $this->mapRenderer);

        if (!$img) {
            return null;
        }

        if ($this->scale !== 1.0) {
            $img = imagescale($img, (int) ($this->mapWidth * $this->scale), (int) ($this->mapHeight * $this->scale));
            assert($img !== false);
        }

        return $img;
    }

    /**
     * @param non-negative-int $x
     * @param non-negative-int $y
     *
     * @return GdImage
     */
    private function doRenderOriginalSize(int $x, int $y): GdImage
    {
        $img = $this->emptyTile();

        foreach ($this->toMapCoordinates($x, $y) as $mapCoordinate) {
            if (!$map = $this->renderMap($mapCoordinate)) {
                continue;
            }

            imagecopy(
                $img,
                $map,
                $mapCoordinate->xDestinationOffset,
                $mapCoordinate->yDestinationOffset,
                $mapCoordinate->xSourceOffset,
                $mapCoordinate->ySourceOffset,
                (int) ($this->mapWidth * $this->scale) - $mapCoordinate->xSourceOffset,
                (int) ($this->mapHeight * $this->scale) - $mapCoordinate->ySourceOffset,
            );
        }

        return $img;
    }

    private function emptyTile(): GdImage
    {
        $img = imagecreatetruecolor($this->tileSize, $this->tileSize);

        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        assert($transparent !== false);
        imagefill($img, 0, 0, $transparent);

        return $img;
    }

    /**
     * @param Bounds $bounds
     * @param positive-int $tileSize
     * @param positive-int $mapWidth
     * @param positive-int $mapHeight
     * @param float $scale
     *
     * @return positive-int
     */
    private static function computeSize(Bounds $bounds, int $tileSize, int $mapWidth, int $mapHeight, float $scale): int
    {
        $realWidth = ($bounds->xMax - $bounds->xMin + 1) * $mapWidth * $scale;
        $realHeight = ($bounds->yMax - $bounds->yMin + 1) * $mapHeight * $scale;

        $size = max($realWidth, $realHeight);
        $tileCount = $size / $tileSize;

        $size = (int) 2 ** ceil(log($tileCount, 2));
        assert($size >= 1);

        return $size;
    }
}
