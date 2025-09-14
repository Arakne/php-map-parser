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
        public int $width,
        public int $height,
        public int $background,

        /**
         * @var list<Cell>
         */
        public array $cells
    ) {}

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function width(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function height(): int
    {
        return $this->height;
    }

    /**
     * @return Cell[]
     */
    public function cells(): array
    {
        return $this->cells;
    }
}
