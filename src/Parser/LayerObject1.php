<?php


namespace Arakne\MapParser\Parser;

/**
 * The first layer object
 */
final class LayerObject1 implements LayerObjectInterface
{
    /**
     * @var int[]
     */
    private $data;


    /**
     * Ground constructor.
     *
     * @param int[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function number(): int
    {
        return (($this->data[0] & 4) << 11) + (($this->data[4] & 1) << 12) + ($this->data[5] << 6) + $this->data[6];
    }

    /**
     * {@inheritdoc}
     */
    public function rotation(): int
    {
        return ($this->data[7] & 48) >> 4;
    }

    /**
     * {@inheritdoc}
     */
    public function flip(): bool
    {
        return ($this->data[7] & 8) >> 3 === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function active(): bool
    {
        return $this->number() !== 0;
    }
}
