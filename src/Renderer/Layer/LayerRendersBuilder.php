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

use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use Closure;

use function array_values;
use function ksort;

/**
 * Builder for a list of layer renderers, with methods to enable/disable common layers
 */
final class LayerRendersBuilder
{
    public const int BACKGROUND_LAYER = 0;
    public const int GROUND_LAYER = 200;
    public const int OBJECT1_LAYER = 300;
    public const int GRID_LAYER = 400;
    public const int OBJECT2_LAYER = 800;

    /**
     * Indexed by layer constant
     *
     * @var array<int, LayerRendererInterface|Closure(SpriteRepositoryInterface, SpriteRepositoryInterface):LayerRendererInterface>
     */
    private array $layers;

    public function __construct(
        private readonly SpriteRepositoryInterface $grounds,
        private readonly SpriteRepositoryInterface $objects,
    ) {
        $this->layers = self::defaultLayers($grounds, $objects);
    }

    /**
     * Set or override a layer renderer at the given level
     *
     * @param int $level The layer level. Higher levels are rendered on top of lower levels.
     * @param LayerRendererInterface|Closure(SpriteRepositoryInterface, SpriteRepositoryInterface):LayerRendererInterface $layer The layer renderer or a factory to create it. The factory will be called with the grounds and objects sprite repositories.
     *
     * @return $this
     */
    public function set(int $level, LayerRendererInterface|Closure $layer): self
    {
        $this->layers[$level] = $layer;

        return $this;
    }

    /**
     * Remove a layer renderer at the given level
     *
     * @param int $level The layer level to remove. Use the layer constants to remove default layers.
     * @return $this
     */
    public function disable(int $level): self
    {
        unset($this->layers[$level]);

        return $this;
    }

    public function enableBackground(): self
    {
        $this->layers[self::BACKGROUND_LAYER] ??= new BackgroundLayerRenderer($this->grounds);
        return $this;
    }

    public function disableBackground(): self
    {
        return $this->disable(self::BACKGROUND_LAYER);
    }

    public function enableGround(): self
    {
        $this->layers[self::GROUND_LAYER] ??= new LayerObjectRenderer($this->grounds, static fn(CellShape $cell) => $cell->data->ground);
        return $this;
    }

    public function disableGround(): self
    {
        return $this->disable(self::GROUND_LAYER);
    }

    public function enableObject1(): self
    {
        $this->layers[self::OBJECT1_LAYER] ??= new LayerObjectRenderer($this->objects, static fn(CellShape $cell) => $cell->data->layer1);
        return $this;
    }

    public function disableObject1(): self
    {
        return $this->disable(self::OBJECT1_LAYER);
    }

    public function enableGrid(): self
    {
        return $this->set(self::GRID_LAYER, new GridLayerRenderer());
    }

    public function disableGrid(): self
    {
        return $this->disable(self::GRID_LAYER);
    }

    public function enableObject2(): self
    {
        $this->layers[self::OBJECT2_LAYER] ??= new LayerObjectRenderer($this->objects, static fn(CellShape $cell) => $cell->data->layer2);
        return $this;
    }

    public function disableObject2(): self
    {
        return $this->disable(self::OBJECT2_LAYER);
    }

    /**
     * @return list<LayerRendererInterface>
     */
    public function build(): array
    {
        $layers = [];

        foreach ($this->layers as $level => $layer) {
            if ($layer instanceof Closure) {
                $layer = $layer($this->grounds, $this->objects);
            }

            $layers[$level] = $layer;
        }

        ksort($layers);

        return array_values($layers);
    }

    /**
     * Get the default layers: background, ground, object1, object2
     *
     * @param SpriteRepositoryInterface $grounds
     * @param SpriteRepositoryInterface $objects
     *
     * @return array<int, LayerRendererInterface> The default layers, indexed by their level
     */
    public static function defaultLayers(SpriteRepositoryInterface $grounds, SpriteRepositoryInterface $objects): array
    {
        return [
            self::BACKGROUND_LAYER => new BackgroundLayerRenderer($grounds),
            self::GROUND_LAYER => new LayerObjectRenderer($grounds, static fn(CellShape $cell) => $cell->data->ground),
            self::OBJECT1_LAYER => new LayerObjectRenderer($objects, static fn(CellShape $cell) => $cell->data->layer1),
            self::OBJECT2_LAYER => new LayerObjectRenderer($objects, static fn(CellShape $cell) => $cell->data->layer2),
        ];
    }
}
