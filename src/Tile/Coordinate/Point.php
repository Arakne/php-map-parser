<?php

namespace Arakne\MapParser\Tile\Coordinate;

/**
 * A point using pixel coordinates
 */
final readonly class Point
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}
}
