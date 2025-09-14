<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;

/**
 * Store the map data
 *
 * @todo add other fields
 */
final readonly class Map
{
    public function __construct(
        public int $id,

        /**
         * @var non-negative-int
         */
        public int $width,

        /**
         * @var non-negative-int
         */
        public int $height,

        /**
         * The background sprite id.
         * If 0, no background is defined.
         */
        public int $background,

        /**
         * @var list<Cell>
         */
        public array $cells,
    ) {}
}
