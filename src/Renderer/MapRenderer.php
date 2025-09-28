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
use Arakne\MapParser\Renderer\Layer\LayerRendererInterface;
use Arakne\MapParser\Renderer\Layer\LayerRendersBuilder;
use GdImage;
use Override;

use function imagecreatetruecolor;

/**
 * Base dofus map renderer
 *
 * Usage:
 * ```php
 * $loader = new MapLoader();
 * $renderer = new MapRenderer(
 *     new LayerRendersBuilder(
 *        new SwfSpriteRepository(glob('path/to/clips/gfx/g*.swf')),
 *        new SwfSpriteRepository(glob('path/to/clips/gfx/o*.swf')),
 *     )->build()
 * );
 *
 * $map = $loader->load(
 *     MapStructure::fromSwfFile(new SwfFile('path/to/mapX.swf')),
 *     new MapKey(getMapKey()),
 * );
 *
 * $img = $renderer->render($map);
 * header('Content-Type: image/png');
 * imagepng($img);
 * ```
 */
final readonly class MapRenderer implements MapRendererInterface
{
    /**
     * The output image width in pixels
     */
    public const int DISPLAY_WIDTH = 742;

    /**
     * The output image height in pixels
     */
    public const int DISPLAY_HEIGHT = 432;

    public const int CELL_WIDTH = 53;
    public const int CELL_HEIGHT = 27;
    public const float CELL_HALF_WIDTH = 2.650000E+001;
    public const float CELL_HALF_HEIGHT = 1.350000E+001;

    public const int LEVEL_HEIGHT = 20;

    /**
     * The default map width in cells
     */
    public const int DEFAULT_WIDTH = 15;

    /**
     * The default map height in cells
     */
    public const int DEFAULT_HEIGHT = 17;

    public function __construct(
        /**
         * The list of layer renderers to apply, in order
         *
         * @var list<LayerRendererInterface>
         * @see LayerRendersBuilder for a builder to create common layers
         */
        private array $layers,
    ) {}

    #[Override]
    public function render(Map $map): GdImage
    {
        $hasCustomSize = $map->width !== self::DEFAULT_WIDTH || $map->height !== self::DEFAULT_HEIGHT;

        if (!$hasCustomSize) {
            $img = imagecreatetruecolor(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);
        } else {
            $img = imagecreatetruecolor(
                ($map->width - 1) * self::CELL_WIDTH,
                ($map->height - 1) * self::CELL_HEIGHT,
            );
        }

        $shapes = CellShape::fromMap($map);

        foreach ($this->layers as $layer) {
            $layer->render($map, $shapes, $img);
        }

        if ($hasCustomSize) {
            $img = MapScale::for($map)->applyToImage($img);
        }

        return $img;
    }
}
