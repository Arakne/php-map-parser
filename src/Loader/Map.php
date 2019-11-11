<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;

/**
 * Store the map data
 *
 * @todo add other fields
 */
final class Map
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var Cell[]
     */
    private $cells;

    /**
     * Map constructor.
     *
     * @param int $id
     * @param int $width
     * @param int $height
     * @param Cell[] $cells
     */
    public function __construct(int $id, int $width, int $height, array $cells)
    {
        $this->id = $id;
        $this->width = $width;
        $this->height = $height;
        $this->cells = $cells;
    }

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
