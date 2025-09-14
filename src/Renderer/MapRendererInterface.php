<?php

namespace Arakne\MapParser\Renderer;


use Arakne\MapParser\Loader\Map;
use GdImage;

/**
 * Render a map
 */
interface MapRendererInterface
{
    /**
     * Render the given map as a GD image
     *
     * @param Map $map
     * @return GdImage
     */
    public function render(Map $map): GdImage;
}
