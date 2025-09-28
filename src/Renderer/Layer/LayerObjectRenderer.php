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
use Arakne\MapParser\Parser\LayerObjectInterface;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use Closure;
use GdImage;
use Override;

use function imagecopy;

/**
 * Render a layer object, by extracting the sprite from swf
 */
final readonly class LayerObjectRenderer implements LayerRendererInterface
{
    public function __construct(
        private SpriteRepositoryInterface $sprites,

        /**
         * The function to get the object from the cell
         *
         * @var Closure(CellShape): LayerObjectInterface
         */
        private Closure $getter,
    ) {}

    #[Override]
    public function render(Map $map, array $cells, GdImage $out): void
    {
        foreach ($cells as $cell) {
            $this->renderCell($cell, $out);
        }
    }

    public function renderCell(CellShape $cell, GdImage $out): void
    {
        if (!$object = $this->getObject($cell)) {
            return;
        }

        $sprite = $this->sprites->get($object->number);

        if (!$sprite->valid) {
            return;
        }

        if ($object->rotation !== 0) {
            $sprite = $sprite->rotate($object->rotation);
        }

        if ($object->flip) {
            $sprite = $sprite->flip();
        }

        $img = $sprite->gd();
        $y = $cell->y + $sprite->offsetY;
        $x = $cell->x + $sprite->offsetX;

        imagecopy($out, $img, (int) $x, (int) $y, 0, 0, (int) $sprite->width, (int) $sprite->height);
    }

    /**
     * Get the layer object from the cell
     *
     * @param CellShape $cell
     *
     * @return LayerObjectInterface|null The layer object, or null if not active
     */
    private function getObject(CellShape $cell): ?LayerObjectInterface
    {
        $object = ($this->getter)($cell);

        if (!$object->active) {
            return null;
        }

        return $object;
    }
}
