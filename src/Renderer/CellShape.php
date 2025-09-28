<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\Cell;
use Arakne\MapParser\Tile\Coordinate\Point;

/**
 * A cell with position in pixel
 */
final readonly class CellShape
{
    private function __construct(
        /**
         * The x position in pixels
         */
        public int $x,

        /**
         * The y position in pixels
         */
        public int $y,

        /**
         * Base cell object
         */
        public Cell $data,

        /**
         * Get the cell id in the map
         */
        public int $id,

        /**
         * The map this cell belongs to
         */
        private Map $map,
    ) {}

    /**
     * Get the pixel coordinates of the cell on the display image
     *
     * This method takes in account the scaling + cropping of the final image
     * if its dimensions are different from the default ones.
     *
     * Null will be returned if the cell is outside the display area.
     *
     * @return Point|null The pixel coordinates on the display image, or null if outside
     */
    public function toDisplayPosition(): ?Point
    {
        $point = MapScale::for($this->map)->applyToCoordinates($this->x, $this->y);

        if ($point->x < 0
            || $point->x > MapRenderer::DISPLAY_WIDTH
            || $point->y < 0
            || $point->y > MapRenderer::DISPLAY_HEIGHT
        ) {
            return null;
        }

        return $point;
    }

    /**
     * Parse a single cell data to cell shape from its cell id
     *
     * @param Map $map
     * @param non-negative-int $cellId
     *
     * @return self|null
     */
    public static function fromCellId(Map $map, int $cellId): ?self
    {
        if (!($cell = $map->cells[$cellId] ?? null)) {
            return null;
        }

        $line = (int) ($cellId / ($map->width * 2 - 1));
        $column = $cellId % ($map->width * 2 - 1);

        if ($column >= $map->width) {
            $column -= $map->width;
            $subLine = 1;
        } else {
            $subLine = 0;
        }

        $x = (int) ($column * MapRenderer::CELL_WIDTH + $subLine * MapRenderer::CELL_HALF_WIDTH);
        $y = (int) ($line * MapRenderer::CELL_HEIGHT + $subLine * MapRenderer::CELL_HALF_HEIGHT - MapRenderer::LEVEL_HEIGHT * ($cell->ground->level - 7));

        return new self($x, $y, $cell, $cellId, $map);
    }

    /**
     * Transform cell data to cell shapes
     *
     * @param Map $map Map to load
     * @param bool $ignoreInactive Ignore the inactive cells ?
     *
     * @return array<int, CellShape> The cell shapes, indexed by cell id
     */
    public static function fromMap(Map $map, bool $ignoreInactive = true): array
    {
        $shapes = [];

        $_loc14 = $map->width - 1;
        $_loc9 = -1;
        $_loc10 = 0;
        $_loc11 = 0;

        foreach ($map->cells as $id => $cell) {
            if ($_loc9 === $_loc14) {
                $_loc9 = 0;
                ++$_loc10;

                if ($_loc11 === 0) {
                    $_loc11 = MapRenderer::CELL_HALF_WIDTH;
                    --$_loc14;
                } else {
                    $_loc11 = 0;
                    ++$_loc14;
                }
            } else {
                ++$_loc9;
            }

            $x = (int) ($_loc9 * MapRenderer::CELL_WIDTH + $_loc11);
            $y = (int) ($_loc10 * MapRenderer::CELL_HALF_HEIGHT - MapRenderer::LEVEL_HEIGHT * ($cell->ground->level - 7));

            if (!$ignoreInactive || $cell->active) {
                $shapes[$id] = new CellShape($x, $y, $cell, $id, $map);
            }
        }

        return $shapes;
    }
}
