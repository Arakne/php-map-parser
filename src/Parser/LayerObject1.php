<?php


namespace Arakne\MapParser\Parser;

/**
 * The first layer object
 */
final class LayerObject1 implements LayerObjectInterface
{
    public int $number {
        get => (($this->data[0] & 4) << 11) + (($this->data[4] & 1) << 12) + ($this->data[5] << 6) + $this->data[6];
    }

    public int $rotation {
        get => ($this->data[7] & 48) >> 4;
    }

    public bool $flip {
        get => ($this->data[7] & 8) >> 3 === 1;
    }

    public bool $active {
        get => $this->number !== 0;
    }

    public function __construct(
        /**
         * The raw cell data
         * @var list<int>
         */
        private readonly array $data
    ) {}
}
