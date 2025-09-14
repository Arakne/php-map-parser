<?php

namespace Arakne\MapParser\Parser;

use function assert;
use function count;

/**
 * Parsed cell data
 *
 * @see https://github.com/Emudofus/Dofus/blob/1.29/ank/battlefield/utils/Compressor.as#L54
 */
final class Cell
{
    /**
     * Check if the cell do not block the line of sight
     */
    public bool $lineOfSight {
        get => ($this->data[0] & 1) === 1;
    }

    /**
     * Get the permitted movement type
     *
     * The value is an int in range [0 - 5] :
     *
     * - 0 means not walkable
     * - 1 means walkable, but not on a road
     * - 2 to 5 means different levels of walkable cells. Bigger is the movement, lower is the weight on pathing
     *
     * @var int<0, 5>
     */
    public int $movement {
        get => ($this->data[2] & 56) >> 3;
    }

    /**
     * Check if the cell is active or not
     */
    public bool $active {
        get => ($this->data[0] & 32) >> 5 === 1;
    }

    /**
     * Get the ground object
     */
    public GroundObject $ground {
        get => $this->ground ??= new GroundObject($this->data);
    }

    /**
     * Get the object on the first layer (placed above the ground, but below creatures and second layer)
     */
    public LayerObject1 $layer1 {
        get => $this->layer1 ??= new LayerObject1($this->data);
    }

    /**
     * Get the object on the second layer (place above all sprites)
     */
    public LayerObject2 $layer2 {
        get => $this->layer2 ??= new LayerObject2($this->data);
    }

    /**
     * Cell constructor.
     *
     * @param int[] $data
     */
    public function __construct(
        /**
         * The raw cell data (10 bytes)
         * @var list<int>
         */
        private readonly array $data,
    ) {
        assert(count($this->data) === 10);
    }
}
