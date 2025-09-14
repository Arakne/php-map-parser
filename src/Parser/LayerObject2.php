<?php

namespace Arakne\MapParser\Parser;

/**
 * The second layer object
 */
final class LayerObject2 implements LayerObjectInterface
{
    public int $number {
        get => (($this->data[0] & 2) << 12) + (($this->data[7] & 1) << 12) + ($this->data[8] << 6) + $this->data[9];
    }

    public int $rotation {
        get => 0;
    }

    public bool $flip {
        get => ($this->data[7] & 4) >> 2 === 1;
    }

    public bool $active {
        get => $this->number !== 0;
    }

    /**
     * Does the object is interactive ?
     */
    public bool $interactive {
        get => ($this->data[7] & 2) >> 1 === 1;
    }

    public function __construct(
        /**
         * The raw cell data
         * @var list<int>
         */
        private readonly array $data,
    ) {}
}
