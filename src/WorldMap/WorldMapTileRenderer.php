<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\Tile\MapCoordinates;
use Closure;
use GdImage;
use Imagick;

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

final class WorldMapTileRenderer
{
    public const int TILE_SIZE = 256;

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
         * Resolve the map from the [X,Y] coordinates
         *
         * @var Closure(MapCoordinates):(string|null)
         */
        private readonly Closure $mapResolver,

        /**
         * The minimal X coordinate of the map set
         */
        private readonly int $Xmin,

        /**
         * The maximal X coordinate of the map set
         */
        private readonly int $Xmax,

        /**
         * The minimal Y coordinate of the map set
         */
        private readonly int $Ymin,

        /**
         * The maximal Y coordinate of the map set
         */
        private readonly int $Ymax,

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
        $this->size = self::computeSize($Xmin, $Xmax, $Ymin, $Ymax, $tileSize);
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
        $mapX = (int) ($x * $this->tileSize / MapRenderer::DISPLAY_WIDTH);
        $mapY = (int) ($y * $this->tileSize / MapRenderer::DISPLAY_HEIGHT);

        if ($mapX + $this->Xmin > $this->Xmax || $mapY + $this->Ymin > $this->Ymax) {
            return [];
        }

        $Xoffset = ($x * $this->tileSize) - ($mapX * MapRenderer::DISPLAY_WIDTH);
        $Yoffset = ($y * $this->tileSize) - ($mapY * MapRenderer::DISPLAY_HEIGHT);

        $map = new MapCoordinates($mapX + $this->Xmin, $mapY + $this->Ymin, $Xoffset, $Yoffset);

        $maps = [$map];

        $hasX = ($x + 1) * $this->tileSize > ($mapX + 1) * MapRenderer::DISPLAY_WIDTH && $mapX + $this->Xmin < $this->Xmax;
        $hasY = ($y + 1) * $this->tileSize > ($mapY + 1) * MapRenderer::DISPLAY_HEIGHT && $mapY + $this->Ymin < $this->Ymax;

        if ($hasX) {
            $maps[] = new MapCoordinates($mapX + $this->Xmin + 1, $mapY + $this->Ymin, 0, $Yoffset, MapRenderer::DISPLAY_WIDTH - $Xoffset, 0);
        }

        if ($hasY) {
            $maps[] = new MapCoordinates($mapX + $this->Xmin, $mapY + $this->Ymin + 1, $Xoffset, 0, 0, MapRenderer::DISPLAY_HEIGHT - $Yoffset);
        }

        if ($hasX && $hasY) {
            $maps[] = new MapCoordinates($mapX + $this->Xmin + 1, $mapY + $this->Ymin + 1, 0, 0, MapRenderer::DISPLAY_WIDTH - $Xoffset, MapRenderer::DISPLAY_HEIGHT - $Yoffset);
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
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
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
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
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
        if (!$map = ($this->mapResolver)($coordinates)) {
            return null;
        }

        // GD doesn't supports partial transparency well, so we use Imagick to resize the image
        $im = new Imagick();
        $im->readImageBlob($map);
        $im->setImageFormat('png32');
        $im->resizeImage(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT, Imagick::FILTER_LANCZOS, 1);

        return imagecreatefromstring($im->getImagesBlob());
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
