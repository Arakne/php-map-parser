<?php

namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\CellShape;
use Intervention\Image\Image;

/**
 * Render for a map layer
 */
interface LayerRendererInterface
{
    /**
     * Prepare loading of the layer sprite
     *
     * @param Map $map
     * @param CellShape[] $cells
     */
    public function prepare(Map $map, array $cells): void;

    /**
     * Render the layer
     *
     * @param Map $map
     * @param array $cells
     * @param Image $out The output image
     */
    public function render(Map $map, array $cells, Image $out): void;
}
