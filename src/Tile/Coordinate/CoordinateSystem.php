<?php

namespace Arakne\MapParser\Tile\Coordinate;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\CellShape;

use function ceil;
use function cos;
use function deg2rad;
use function floor;
use function log;
use function max;
use function min;
use function tan;

/**
 * Stores all properties of the coordinate system, and provides methods to manipulate coordinates.
 */
final readonly class CoordinateSystem
{
    public function __construct(
        /**
         * The game map coordinate bounds
         */
        private Bounds $bounds,

        /**
         * Size of a tile in pixels (usually 256)
         *
         * @var positive-int
         */
        private int $tileSize,

        /**
         * Maximum zoom level (i.e. when 1 pixel on the map corresponds to 1 pixel on the screen)
         *
         * @var non-negative-int
         */
        private int $maxZoom,

        /**
         * Width of a chunk / game map in pixels
         *
         * @var positive-int
         */
        private int $chunkWidth,

        /**
         * Height of a chunk / game map in pixels
         *
         * @var positive-int
         */
        private int $chunkHeight,

        /**
         * Scale factor for chunk (1.0 = 100%)
         *
         * @var float
         */
        private float $scale,
    ) {}

    /**
     * Convert LatLongBounds to Bounds in the game map coordinate system
     * The bounds are inclusive on both sides (if at least one pixel of a map is visible, the map coordinates are included)
     *
     * @param LatLongBounds $latLongBounds
     * @return Bounds
     */
    public function toMapBounds(LatLongBounds $latLongBounds): Bounds
    {
        $xMinPixel = $this->longitudeToPixel($latLongBounds->west);
        $xMaxPixel = $this->longitudeToPixel($latLongBounds->east);
        $yMinPixel = $this->latitudeToPixel($latLongBounds->north);
        $yMaxPixel = $this->latitudeToPixel($latLongBounds->south);

        $chunkWidth = $this->chunkWidth * $this->scale;
        $chunkHeight = $this->chunkHeight * $this->scale;

        $xMinPixel += $this->bounds->xMin * $chunkWidth;
        $xMaxPixel += $this->bounds->xMin * $chunkWidth;
        $yMinPixel += $this->bounds->yMin * $chunkHeight;
        $yMaxPixel += $this->bounds->yMin * $chunkHeight;

        return new Bounds(
            xMin: max($this->bounds->xMin, (int) floor($xMinPixel / $chunkWidth)),
            xMax: min($this->bounds->xMax, (int) ceil($xMaxPixel / $chunkWidth) - 1),
            yMin: max($this->bounds->yMin, (int) floor($yMinPixel / $chunkHeight)),
            yMax: min($this->bounds->yMax, (int) ceil($yMaxPixel / $chunkHeight) - 1),
        );
    }

    /**
     * Convert a point in absolute pixel position to latitude and longitude
     *
     * @param Point $point
     * @return LatLong
     */
    public function pointToLatLong(Point $point): LatLong
    {
        $n = 2 ** $this->maxZoom * 256;
        $lon = $point->x / $n * 360.0 - 180.0;
        $latRad = atan(sinh(M_PI * (1 - 2 * $point->y / $n)));
        $lat = rad2deg($latRad);

        return new LatLong($lat, $lon);
    }

    /**
     * Get the latitude and longitude of a cell
     * The point returned is the middle of the cell
     *
     * @param Map $map The map containing the cell
     * @param non-negative-int $cellId The cell id to convert
     *
     * @return LatLong|null The cell coordinates, or null if the cell is invalid or out of bounds
     */
    public function cellToLatLong(Map $map, int $cellId): ?LatLong
    {
        $point = $this->cellToPoint($map, $cellId);

        return $point ? $this->pointToLatLong($point) : null;
    }

    /**
     * Get the absolute cell position in pixels
     * The point returned is the middle of the cell
     *
     * @param Map $map The map containing the cell
     * @param non-negative-int $cellId The cell id to convert
     *
     * @return Point|null The cell position in pixels, or null if the cell is invalid or out of bounds
     */
    public function cellToPoint(Map $map, int $cellId): ?Point
    {
        $cell = CellShape::fromCellId($map, $cellId);

        if (!$cell) {
            return null;
        }

        $mapX = $map->x;
        $mapY = $map->y;

        if ($mapX < $this->bounds->xMin || $mapX > $this->bounds->xMax || $mapY < $this->bounds->yMin || $mapY > $this->bounds->yMax) {
            return null;
        }

        $mapX -= $this->bounds->xMin;
        $mapY -= $this->bounds->yMin;
        $mapX *= $this->chunkWidth;
        $mapY *= $this->chunkHeight;

        // @todo handle map scale and cropping on bigger maps
        return new Point(
            (int) round(($mapX + $cell->x) * $this->scale),
            (int) round(($mapY + $cell->y) * $this->scale),
        );
    }

    /**
     * Convert a latitude value to a pixel Y coordinate at the maximum zoom level
     *
     * @param float $latitude
     * @return int
     */
    private function latitudeToPixel(float $latitude): int
    {
        $n = (1 << $this->maxZoom) * $this->tileSize;

        return (int) ((1.0 - log(tan(deg2rad($latitude)) + 1.0 / cos(deg2rad($latitude))) / M_PI) / 2.0 * $n);
    }

    /**
     * Convert a longitude value to a pixel X coordinate at the maximum zoom level
     *
     * @param float $longitude
     * @return int
     */
    private function longitudeToPixel(float $longitude): int
    {
        $n = (1 << $this->maxZoom) * $this->tileSize;

        return (int) (($longitude + 180.0) / 360.0 * $n);
    }
}
