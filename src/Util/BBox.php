<?php

namespace Arakne\MapParser\Util;

use Arakne\MapParser\Renderer\MapRenderer;

final readonly class BBox
{
    public function __construct(
        public float $west,
        public float $south,
        public float $east,
        public float $north,
    ) {}

    public static function fromString(string $str): self
    {
        $parts = explode(',', $str);

        return new self(
            west: (float) $parts[0],
            south: (float) $parts[1],
            east: (float) $parts[2],
            north: (float) $parts[3],
        );
    }
}
