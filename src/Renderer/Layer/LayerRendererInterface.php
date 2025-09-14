<?php

namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\CellShape;
use GdImage;

/**
 * Render for a map layer
 */
interface LayerRendererInterface
{
    /**
     * Render the layer
     *
     * @param Map $map
     * @param CellShape[] $cells
     * @param GdImage $out The output image
     */
    public function render(Map $map, array $cells, GdImage $out): void;
}
