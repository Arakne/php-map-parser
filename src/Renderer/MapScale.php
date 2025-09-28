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

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Tile\Coordinate\Point;
use GdImage;

use function assert;
use function imagecopyresampled;
use function imagecreatetruecolor;
use function imagesx;
use function imagesy;

/**
 * Handle the scaling and offset to apply to a map when rendering it
 */
final readonly class MapScale
{
    public function __construct(
        /**
         * The scaling factor to apply to the map (1.0 = 100%)
         */
        public float $scale,

        /**
         * The horizontal offset to apply to the map in pixels
         * This offset can be negative if the map is wider than the display area
         */
        public int $offsetX,

        /**
         * The vertical offset to apply to the map in pixels
         * This offset can be negative if the map is taller than the display area
         */
        public int $offsetY,

        /**
         * The actual width of the map after scaling, in pixels, but before cropping
         *
         * @var non-negative-int
         */
        public int $scaledWidth,

        /**
         * The actual height of the map after scaling, in pixels, but before cropping
         *
         * @var non-negative-int
         */
        public int $scaledHeight,
    ) {}

    /**
     * Transform the given pixel coordinates by applying the scaling + offset
     *
     * @param int $x
     * @param int $y
     *
     * @return Point
     */
    public function applyToCoordinates(int $x, int $y): Point
    {
        return new Point(
            (int) ($x * $this->scale) + $this->offsetX,
            (int) ($y * $this->scale) + $this->offsetY,
        );
    }

    /**
     * Resize and crop the given image to fit in the display area
     *
     * @param GdImage $img The original map image
     * @return GdImage The resized and cropped image
     */
    public function applyToImage(GdImage $img): GdImage
    {
        $result = imagecreatetruecolor(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        assert($result !== false);

        imagecopyresampled(
            $result,
            $img,
            $this->offsetX,
            $this->offsetY,
            0,
            0,
            $this->scaledWidth,
            $this->scaledHeight,
            imagesx($img),
            imagesy($img),
        );

        return $result;
    }

    /**
     * Get the scaling + offset to apply to a map of arbitrary size to fit in the display area
     *
     * @param Map $map The map to compute the scale for
     *
     * @see https://github.com/Emudofus/Dofus/blob/1.29/ank/battlefield/mc/Container.as#L154
     */
    public static function for(Map $map): MapScale
    {
        if ($map->height === MapRenderer::DEFAULT_HEIGHT && $map->width === MapRenderer::DEFAULT_WIDTH) {
            return new MapScale(1.0, 0, 0, MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        }

        // Scaling is only applied if both dimensions are greater than the default size
        // Otherwise, the map is displayed at its original size and simply cropped/centered
        if ($map->height > MapRenderer::DEFAULT_HEIGHT && $map->width > MapRenderer::DEFAULT_WIDTH) {
            $scale = $map->height > $map->width
                ? MapRenderer::DISPLAY_WIDTH / (($map->width - 1) * MapRenderer::CELL_WIDTH)
                : MapRenderer::DISPLAY_HEIGHT / (($map->height - 1) * MapRenderer::CELL_HEIGHT)
            ;

            $actualWidth = (int) (($map->width - 1) * MapRenderer::CELL_WIDTH * $scale);
            $actualHeight = (int) (($map->height - 1) * MapRenderer::CELL_HEIGHT * $scale);
        } else {
            $scale = 1.0;
            $actualWidth = ($map->width - 1) * MapRenderer::CELL_WIDTH;
            $actualHeight = ($map->height - 1) * MapRenderer::CELL_HEIGHT;
        }

        // Map has the correct size, no need to crop
        if ($actualWidth === MapRenderer::DISPLAY_WIDTH && $actualHeight === MapRenderer::DISPLAY_HEIGHT) {
            return new MapScale($scale, 0, 0, $actualWidth, $actualHeight);
        }

        assert($actualWidth >= 0 && $actualHeight >= 0);

        $offsetX = (MapRenderer::DISPLAY_WIDTH - $actualWidth) / 2;
        $offsetY = (MapRenderer::DISPLAY_HEIGHT - $actualHeight) / 2;

        return new MapScale($scale, (int) $offsetX, (int) $offsetY, $actualWidth, $actualHeight);
    }
}
