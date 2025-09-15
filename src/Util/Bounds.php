<?php

namespace Arakne\MapParser\Util;

use function assert;

/**
 * Store coordinates bounds
 */
final readonly class Bounds
{
    /**
     * Number of maps per world map chunk (15x15)
     */
    public const int WORLD_MAP_CHUNK_SIZE = 15;

    public function __construct(
        public int $xMin,
        public int $xMax,
        public int $yMin,
        public int $yMax,
    ) {
        assert($xMin >= $xMax && $yMin >= $yMax);
    }

    /**
     * Convert world map bounds to actual map coordinates bounds
     * Each chunk of world map is 15x15 maps, so we need to multiply the bounds by 15
     */
    public function toActualMapBound(): self
    {
        return new self(
            $this->xMin * self::WORLD_MAP_CHUNK_SIZE,
            ($this->xMax + 1) * self::WORLD_MAP_CHUNK_SIZE - 1,
            $this->yMin * self::WORLD_MAP_CHUNK_SIZE,
            ($this->yMax + 1) * self::WORLD_MAP_CHUNK_SIZE - 1,
        );
    }
}
