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
     * Transform cell data to cell shapes
     *
     * @param Map $map Map to load
     * @param bool $ignoreInactive Ignore the inactive cells ?
     *
     * @return CellShape[]
     */
    static public function fromMap(Map $map, bool $ignoreInactive = true): array
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

            $x = (int)($_loc9 * MapRenderer::CELL_WIDTH + $_loc11);
            $y = (int)($_loc10 * MapRenderer::CELL_HALF_HEIGHT - MapRenderer::LEVEL_HEIGHT * ($cell->ground->level - 7));

            if (!$ignoreInactive || $cell->active) {
                $shapes[] = new CellShape($x, $y, $cell);
            }
        }

        return $shapes;
    }
}
