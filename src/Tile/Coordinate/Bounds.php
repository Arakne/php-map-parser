<?php

namespace Arakne\MapParser\Tile\Coordinate;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\TileRenderer;

use function assert;
use function cos;
use function deg2rad;
use function log;
use function max;
use function min;
use function tan;

/**
 * Store coordinates bounds
 */
final readonly class Bounds
{
    /**
     * Number of maps per world map chunk (15x15)
     */
    public const int WORLD_MAP_CHUNK_SIZE = 15;

    public function __construct(
        public int $xMin,
        public int $xMax,
        public int $yMin,
        public int $yMax,
    ) {
        assert($xMin <= $xMax && $yMin <= $yMax);
    }

    public function inBBox(LatLongBounds $bbox, int $zoom): self
    {
        $n = 2 ** $zoom * TileRenderer::TILE_SIZE;

        $xtileMin = (int) (($bbox->west + 180.0) / 360.0 * $n);
        $xtileMax = (int) (($bbox->east + 180.0) / 360.0 * $n);
        $ytileMin = (int) ((1.0 - log(tan(deg2rad($bbox->north)) + 1.0 / cos(deg2rad($bbox->north))) / M_PI) / 2.0 * $n);
        $ytileMax = (int) ((1.0 - log(tan(deg2rad($bbox->south)) + 1.0 / cos(deg2rad($bbox->south))) / M_PI) / 2.0 * $n);

        $mapWidth = MapRenderer::DISPLAY_WIDTH * 16 / 15;
        $mapHeight = MapRenderer::DISPLAY_HEIGHT * 16 / 15;

        $xtileMin += $this->xMin * $mapWidth;
        $xtileMax += $this->xMin * $mapWidth;
        $ytileMin += $this->yMin * $mapHeight;
        $ytileMax += $this->xMin * $mapHeight;

        return new self(
            xMin: max($this->xMin, (int) ($xtileMin / $mapWidth)),
            xMax: min($this->xMax, (int) ($xtileMax / $mapWidth)),
            yMin: max($this->yMin, (int) ($ytileMin / $mapHeight)),
            yMax: min($this->yMax, (int) ($ytileMax / $mapHeight)),
        );
    }

    /**
     * Convert world map bounds to actual map coordinates bounds
     * Each chunk of world map is 15x15 maps, so we need to multiply the bounds by 15
     */
    public function toActualMapBound(): self
    {
        return new self(
            $this->xMin * self::WORLD_MAP_CHUNK_SIZE,
            ($this->xMax + 1) * self::WORLD_MAP_CHUNK_SIZE - 1,
            $this->yMin * self::WORLD_MAP_CHUNK_SIZE,
            ($this->yMax + 1) * self::WORLD_MAP_CHUNK_SIZE - 1,
        );
    }
}
