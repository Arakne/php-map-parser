<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\Cell;

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
    ) {}

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

        return new self($x, $y, $cell);
    }

    /**
     * Transform cell data to cell shapes
     *
     * @param Map $map Map to load
     * @param bool $ignoreInactive Ignore the inactive cells ?
     *
     * @return CellShape[]
     */
    public static function fromMap(Map $map, bool $ignoreInactive = true): array
    {
        $shapes = [];

        $_loc14 = $map->width - 1;
        $_loc9 = -1;
        $_loc10 = 0;
        $_loc11 = 0;

        foreach ($map->cells as $cell) {
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
                $shapes[] = new CellShape($x, $y, $cell);
            }
        }

        return $shapes;
    }
}
