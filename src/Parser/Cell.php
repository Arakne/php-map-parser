<?php

namespace Arakne\MapParser\Parser;

/**
 * Parsed cell data
 *
 * @see https://github.com/Emudofus/Dofus/blob/1.29/ank/battlefield/utils/Compressor.as#L54
 * @todo filter layers
 */
final class Cell
{
    /**
     * @var int[]
     */
    private $data;


    /**
     * Cell constructor.
     *
     * @param int[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check if the cell do not block the line of sight
     */
    public function lineOfSight(): bool
    {
        return ($this->data[0] & 1) === 1;
    }

    /**
     * Get the permitted movement type
     *
     * The value is an int in range [0 - 5] :
     *
     * - 0 means not walkable
     * - 1 means walkable, but not on a road
     * - 2 to 5 means different levels of walkable cells. Bigger is the movement, lower is the weight on pathing
     */
    public function movement(): int
    {
        return ($this->data[2] & 56) >> 3;
    }

    /**
     * Check if the cell contains an interactive object
     */
    public function interactive(): bool
    {
        return ($this->data[7] & 2) >> 1 === 1;
    }

    /**
     * Get the cell object id
     */
    public function objectId(): int
    {
        return (($this->data[0] & 2) << 12) + (($this->data[7] & 1) << 12) + ($this->data[8] << 6) + $this->data[9];
    }

    /**
     * Check if the cell is active or not
     */
    public function active(): bool
    {
        return ($this->data[0] & 32) >> 5 === 1;
    }

    /**
     * Get the ground object
     *
     * @return GroundObject
     */
    public function ground(): GroundObject
    {
        return new GroundObject($this->data);
    }

    /**
     * Get the object on the first layer
     *
     * @return LayerObject1
     */
    public function layer1(): LayerObject1
    {
        return new LayerObject1($this->data);
    }

    /**
     * Get the object on the second layer
     *
     * @return LayerObject2
     */
    public function layer2(): LayerObject2
    {
        return new LayerObject2($this->data);
    }
}
