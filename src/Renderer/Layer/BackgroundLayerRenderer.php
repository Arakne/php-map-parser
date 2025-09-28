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
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use GdImage;
use Override;

/**
 * Renders the background layer of the map.
 */
final readonly class BackgroundLayerRenderer implements LayerRendererInterface
{
    public function __construct(
        /**
         * The ground sprite repository.
         */
        private SpriteRepositoryInterface $repository,
    ) {}

    #[Override]
    public function render(Map $map, array $cells, GdImage $out): void
    {
        if ($map->background === 0) {
            return;
        }

        $bg = $this->repository->get($map->background);

        if ($bg->valid) {
            imagecopy($out, $bg->gd(), (int) $bg->offsetX, (int) $bg->offsetY, 0, 0, (int) $bg->width, (int) $bg->height);
        }
    }
}
