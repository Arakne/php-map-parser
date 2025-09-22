<?php

namespace Arakne\MapParser\Tile\Coordinate;

/**
 * Coordinate using latitude and longitude
 */
final readonly class LatLong
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}
}
