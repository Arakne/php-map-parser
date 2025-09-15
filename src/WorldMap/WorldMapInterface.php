<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Util\Bounds;

/**
 * Represents a dofus world map file
 * There are located in Dofus/clips/maps directory
 */
interface WorldMapInterface
{
    /**
     * Get the map bounds (min and max coordinates)
     *
     * Note: because each chunk corresponds to 15x15 maps, those coordinates are 1/15th of actual map coordinates
     */
    public function bounds(): Bounds;

    /**
     * Get the chunk image data (PNG) at given coordinates
     * If the chunk does not exist, null is returned
     *
     * The returned image must have same dimensions as maps:
     * - width: {@see MapRenderer::DISPLAY_WIDTH}
     * - height: {@see MapRenderer::DISPLAY_HEIGHT}
     *
     * @param int $x
     * @param int $y
     *
     * @return string|null
     */
    public function chunk(int $x, int $y): ?string;
}
