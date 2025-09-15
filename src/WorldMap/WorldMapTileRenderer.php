<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\Tile\MapCoordinates;
use Arakne\MapParser\Util\Bounds;
use GdImage;

use function assert;
use function ceil;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecopy;
use function imagecopyresampled;
use function imagecreatefromstring;
use function imagecreatetruecolor;
use function imagefill;
use function imagesavealpha;
use function log;
use function max;

/**
 * Render dofus world maps as square tiles compatible with leaflet or other tile-based map viewers.
 */
final class WorldMapTileRenderer
{
    public const int TILE_SIZE = 256;

    private readonly Bounds $bounds;

    /**
     * The size of a size of the complete map in tiles count
     * This value will be rounded to the next power of 2
     *
     * @var positive-int
     */
    public readonly int $size;

    /**
     * The maximum zoom level
     * This value is log2($size)
     *
     * @var non-negative-int
     */
    public readonly int $maxZoom;

    public function __construct(
        /**
         * The world map to render
         */
        private readonly WorldMapInterface $worldMap,

        /**
         * The tile size in pixels (default: 256)
         * This value should be a power of 2, so it can be evenly divided at each zoom level
         *
         * This value is used for both width and height
         *
         * @var positive-int
         */
        private readonly int $tileSize = self::TILE_SIZE,
    ) {
        $this->bounds = $worldMap->bounds();
        $this->size = self::computeSize($this->bounds->xMin, $this->bounds->xMax, $this->bounds->yMin, $this->bounds->yMax, $tileSize);
        // @phpstan-ignore assign.propertyType
        $this->maxZoom = (int) log($this->size, 2);
    }

    /**
     * Convert tile coordinates to map coordinates
     * Because tiles can overlap multiple maps, this function can return multiple map coordinates
     *
     * @param non-negative-int $x
     * @param non-negative-int $y
     *
     * @return MapCoordinates[]
     */
    public function toMapCoordinates(int $x, int $y): array
    {
        $xMin = $this->bounds->xMin;
        $xMax = $this->bounds->xMax;
        $yMin = $this->bounds->yMin;
        $yMax = $this->bounds->yMax;

        $mapX = (int) ($x * $this->tileSize / MapRenderer::DISPLAY_WIDTH);
        $mapY = (int) ($y * $this->tileSize / MapRenderer::DISPLAY_HEIGHT);

        if ($mapX + $xMin > $xMax || $mapY + $yMin > $yMax) {
            return [];
        }

        $Xoffset = ($x * $this->tileSize) - ($mapX * MapRenderer::DISPLAY_WIDTH);
        $Yoffset = ($y * $this->tileSize) - ($mapY * MapRenderer::DISPLAY_HEIGHT);

        $map = new MapCoordinates($mapX + $xMin, $mapY + $yMin, $Xoffset, $Yoffset);

        $maps = [$map];

        $hasX = ($x + 1) * $this->tileSize > ($mapX + 1) * MapRenderer::DISPLAY_WIDTH && $mapX + $xMin < $xMax;
        $hasY = ($y + 1) * $this->tileSize > ($mapY + 1) * MapRenderer::DISPLAY_HEIGHT && $mapY + $yMin < $yMax;

        if ($hasX) {
            $maps[] = new MapCoordinates($mapX + $xMin + 1, $mapY + $yMin, 0, $Yoffset, MapRenderer::DISPLAY_WIDTH - $Xoffset, 0);
        }

        if ($hasY) {
            $maps[] = new MapCoordinates($mapX + $xMin, $mapY + $yMin + 1, $Xoffset, 0, 0, MapRenderer::DISPLAY_HEIGHT - $Yoffset);
        }

        if ($hasX && $hasY) {
            $maps[] = new MapCoordinates($mapX + $xMin + 1, $mapY + $yMin + 1, 0, 0, MapRenderer::DISPLAY_WIDTH - $Xoffset, MapRenderer::DISPLAY_HEIGHT - $Yoffset);
        }

        return $maps;
    }

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
    public function render(int $x, int $y, int $zoom = 0): GdImage
    {
        if ($zoom > $this->maxZoom) {
            return $this->renderUpscaled($x, $y, $zoom);
        }

        if ($zoom === $this->maxZoom) {
            return $this->renderOriginalSize($x, $y);
        }

        $tileCount = $this->size >> $zoom;
        assert($tileCount > 0);

        $startX = (int) ($x * $tileCount);
        $startY = (int) ($y * $tileCount);

        $img = imagecreatetruecolor($this->tileSize, $this->tileSize);
        assert($img !== false);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        assert($transparent !== false);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, $transparent);
        $subtileSize = $this->tileSize / $tileCount;

        for ($x = 0; $x < $tileCount; ++$x) {
            for ($y = 0; $y < $tileCount; ++$y) {
                $gd = $this->renderOriginalSize($startX + $x, $startY + $y);

                imagecopyresampled($img, $gd, (int) ($x * $subtileSize), (int) ($y * $subtileSize), 0, 0, (int) $subtileSize, (int) $subtileSize, $this->tileSize, $this->tileSize);
            }
        }

        return $img;
    }

    public function renderUpscaled(int $x, int $y, int $zoom): GdImage
    {
        $factor = 2 ** ($zoom - $this->maxZoom);

        $tileX = (int) ($x / $factor);
        $tileY = (int) ($y / $factor);

        assert($tileX >= 0 && $tileY >= 0);

        $img = imagecreatetruecolor($this->tileSize, $this->tileSize);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $tile = $this->renderOriginalSize($tileX, $tileY);

        imagecopyresampled(
            $img,
            $tile,
            0,
            0,
            ($x % $factor) * ($this->tileSize / $factor),
            ($y % $factor) * ($this->tileSize / $factor),
            $this->tileSize,
            $this->tileSize,
            $this->tileSize / $factor,
            $this->tileSize / $factor,
        );

        return $img;
    }

    /**
     * Render a single tile at the given [X,Y] coordinates with the maximum detail (i.e. max zoom)
     * Coordinates are in tile space, not map space
     *
     * @param non-negative-int $x
     * @param non-negative-int $y
     *
     * @return GdImage
     */
    public function renderOriginalSize(int $x, int $y): GdImage
    {
        $img = imagecreatetruecolor($this->tileSize, $this->tileSize);
        assert($img !== false);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        assert($transparent !== false);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, $transparent);

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
                MapRenderer::DISPLAY_WIDTH - $mapCoordinate->xSourceOffset,
                MapRenderer::DISPLAY_HEIGHT - $mapCoordinate->ySourceOffset,
            );
        }

        return $img;
    }

    private function renderMap(MapCoordinates $coordinates): ?GdImage
    {
        if (!$map = $this->worldMap->chunk($coordinates->x, $coordinates->y)) {
            return null;
        }

        $img = imagecreatefromstring($map);
        assert($img !== false);

        return $img;
    }

    /**
     * @param int $xMin
     * @param int $xMax
     * @param int $yMin
     * @param int $yMax
     * @param positive-int $tileSize
     *
     * @return positive-int
     */
    private static function computeSize(int $xMin, int $xMax, int $yMin, int $yMax, int $tileSize): int
    {
        $realWidth = ($xMax - $xMin + 1) * MapRenderer::DISPLAY_WIDTH;
        $realHeight = ($yMax - $yMin + 1) * MapRenderer::DISPLAY_HEIGHT;

        $size = max($realWidth, $realHeight);
        $tileCount = $size / $tileSize;

        $size = (int) 2 ** ceil(log($tileCount, 2));
        assert($size >= 1);

        return $size;
    }
}
