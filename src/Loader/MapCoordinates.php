<?php

namespace Arakne\MapParser\Loader;

/**
 * Store map coordinates and optional sub-area id
 */
final readonly class MapCoordinates
{
    public function __construct(
        public int $x,
        public int $y,
        public ?int $subAreaId = null,
    ) {}
}
