<?php

namespace Arakne\MapParser\Tile\Coordinate;

use function count;

/**
 * Geographical bounding box using latitude and longitude
 */
final readonly class LatLongBounds
{
    public function __construct(
        public float $west,
        public float $south,
        public float $east,
        public float $north,
    ) {}

    /**
     * Convert CSV string to LatLongBounds
     *
     * @param string $str
     *
     * @return self|null The bounds or null if the string is invalid
     */
    public static function fromString(string $str): ?self
    {
        $parts = explode(',', $str, 4);

        if (count($parts) !== 4) {
            return null;
        }

        return new self(
            west: (float) $parts[0],
            south: (float) $parts[1],
            east: (float) $parts[2],
            north: (float) $parts[3],
        );
    }
}
