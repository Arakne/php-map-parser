<?php

namespace Arakne\MapParser\Util;

use function assert;

/**
 * Store coordinates bounds
 */
final readonly class Bounds
{
    public function __construct(
        public int $xMin,
        public int $xMax,
        public int $yMin,
        public int $yMax,
    ) {
        assert($xMin >= $xMax && $yMin >= $yMax);
    }
}
