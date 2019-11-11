<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\Cell;

/**
 * A cell with position in pixel
 */
final class CellShape
{
    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;

    /**
     * @var Cell
     */
    private $data;


    /**
     * CellShape constructor.
     *
     * @param int $x
     * @param int $y
     * @param Cell $data
     */
    public function __construct(int $x, int $y, Cell $data)
    {
        $this->x = $x;
        $this->y = $y;
        $this->data = $data;
    }

    /**
     * The x position in pixels
     *
     * @return int
     */
    public function x(): int
    {
        return $this->x;
    }

    /**
     * The y position in pixels
     *
     * @return int
     */
    public function y(): int
    {
        return $this->y;
    }

    /**
     * @return Cell
     */
    public function data(): Cell
    {
        return $this->data;
    }

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

        $_loc14 = $map->width() - 1;
        $_loc9 = -1;
        $_loc10 = 0;
        $_loc11 = 0;

        foreach ($map->cells() as $cell) {
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
            $y = (int)($_loc10 * MapRenderer::CELL_HALF_HEIGHT - MapRenderer::LEVEL_HEIGHT * ($cell->ground()->level() - 7));

            if (!$ignoreInactive || $cell->active()) {
                $shapes[] = new CellShape($x, $y, $cell);
            }
        }

        return $shapes;
    }
}
