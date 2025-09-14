<?php


namespace Arakne\MapParser\Parser;

/**
 * The ground layer object
 */
final class GroundObject implements LayerObjectInterface
{
    public int $number {
        get => (($this->data[0] & 24) << 6) + (($this->data[2] & 7) << 6) + $this->data[3];
    }

    public int $rotation {
        get => ($this->data[1] & 48) >> 4;
    }

    public bool $flip {
        get => ($this->data[4] & 2) >> 1 === 1;
    }

    public bool $active {
         get => $this->number !== 0;
    }

    /**
     * Get the ground elevation level
     */
    public int $level {
        get => $this->data[1] & 15;
    }

    /**
     * Get the ground slope
     */
    public int $slope {
        get => ($this->data[4] & 60) >> 2;
    }

    public function __construct(
        /**
         * The raw cell data
         * @var list<int>
         */
        private readonly array $data,
    ) {}
}
