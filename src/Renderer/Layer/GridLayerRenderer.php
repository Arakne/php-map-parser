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

namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\CellShape;
use GdImage;
use Override;

use function assert;
use function imagecolorallocatealpha;
use function imageline;
use function round;

/**
 * Render the grid layer
 *
 * @see https://github.com/Emudofus/Dofus/blob/1.29/ank/battlefield/GridHandler.as
 */
final readonly class GridLayerRenderer implements LayerRendererInterface
{
    /**
     * Coordinates of the 4 corners of each cell, indexed by ground slope
     *
     * Format:
     * CELL_COORD[SLOPE][CORNER] = [X, Y]
     */
    public const array CELL_COORD = [[[-2.650000E+001, 0], [0, -1.350000E+001], [2.650000E+001, 0], [0, 1.350000E+001]], [[-2.650000E+001, -20], [0, -1.350000E+001], [2.650000E+001, 0], [0, 1.350000E+001]], [[-2.650000E+001, 0], [0, -3.350000E+001], [2.650000E+001, 0], [0, 1.350000E+001]], [[-2.650000E+001, -20], [0, -3.350000E+001], [2.650000E+001, 0], [0, 1.350000E+001]], [[-2.650000E+001, 0], [0, -1.350000E+001], [2.650000E+001, -20], [0, 1.350000E+001]], [[-2.650000E+001, -20], [0, -1.350000E+001], [2.650000E+001, -20], [0, 1.350000E+001]], [[-2.650000E+001, 0], [0, -3.350000E+001], [2.650000E+001, -20], [0, 1.350000E+001]], [[-2.650000E+001, -20], [0, -3.350000E+001], [2.650000E+001, -20], [0, 1.350000E+001]], [[-2.650000E+001, 0], [0, -1.350000E+001], [2.650000E+001, 0], [0, -6.500000E+000]], [[-2.650000E+001, -20], [0, -1.350000E+001], [2.650000E+001, 0], [0, -6.500000E+000]], [[-2.650000E+001, 0], [0, -3.350000E+001], [2.650000E+001, 0], [0, -6.500000E+000]], [[-2.650000E+001, -20], [0, -3.350000E+001], [2.650000E+001, 0], [0, -6.500000E+000]], [[-2.650000E+001, 0], [0, -1.350000E+001], [2.650000E+001, -20], [0, -6.500000E+000]], [[-2.650000E+001, -20], [0, -1.350000E+001], [2.650000E+001, -20], [0, -6.500000E+000]], [[-2.650000E+001, 0], [0, -3.350000E+001], [2.650000E+001, -20], [0, -6.500000E+000]]];

    public function __construct(
        /**
         * The grid line color (hex RGB)
         */
        private int $color = 0xFFFFFF,

        /**
         * The grid line alpha transparency (100 = opaque, 0 = invisible)
         *
         * @var int<0, 100>
         */
        private int $alpha = 30,
    ) {}

    #[Override]
    public function render(Map $map, array $cells, GdImage $out): void
    {
        $alpha = (int) (127 - (($this->alpha / 100.0) * 127));
        assert($alpha >= 0 && $alpha <= 127);
        $gridColor = imagecolorallocatealpha(
            $out,
            ($this->color >> 16) & 0xFF,
            ($this->color >> 8) & 0xFF,
            $this->color & 0xFF,
            $alpha,
        );
        assert($gridColor !== false);

        $inaccessibleCells = [];

        foreach ($cells as $cell) {
            //  phpstorm is dumb
            // @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue
            assert($cell instanceof CellShape);

            $data = $cell->data;

            if (!$data->active) {
                continue;
            }

            if ($data->movement === 0 || !$data->lineOfSight) {
                $inaccessibleCells[$cell->id] = $cell;
                continue;
            }

            $coords = self::CELL_COORD[$data->ground->slope - 1];

            $x = $cell->x + $coords[0][0];
            $y = $cell->y + $coords[0][1];

            imageline($out, (int) round($x), (int) round($y), (int) round($x = $cell->x + $coords[1][0]), (int) round($y = $cell->y + $coords[1][1]), $gridColor);
            imageline($out, (int) round($x), (int) round($y), (int) round($cell->x + $coords[2][0]), (int) round($cell->y + $coords[2][1]), $gridColor);
        }

        foreach ($inaccessibleCells as $cell) {
            // North-West and North-East cells
            foreach ([-$map->width, -$map->width + 1] as $index => $offset) {
                $neighbourId = $cell->id + $offset;
                $neighbour = $cells[$neighbourId] ?? null;

                if (isset($inaccessibleCells[$neighbourId]) || !$neighbour || !$neighbour->data->active) {
                    continue;
                }

                $coords1 = self::CELL_COORD[$cell->data->ground->slope - 1];

                $x1 = $cell->x + $coords1[$index][0];
                $y1 = $cell->y + $coords1[$index][1];
                $x2 = $cell->x + $coords1[$index + 1][0];
                $y2 = $cell->y + $coords1[$index + 1][1];

                imageline($out, (int) round($x1), (int) round($y1), (int) round($x2), (int) round($y2), $gridColor);
            }
        }
    }
}
