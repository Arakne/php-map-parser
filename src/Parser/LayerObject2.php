<?php


namespace Arakne\MapParser\Parser;

/**
 * The second layer object
 */
final class LayerObject2 implements LayerObjectInterface
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
        return (($this->data[0] & 2) << 12) + (($this->data[7] & 1) << 12) + ($this->data[8] << 6) + $this->data[9];
    }

    /**
     * {@inheritdoc}
     */
    public function rotation(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function flip(): bool
    {
        return ($this->data[7] & 4) >> 2 === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function active(): bool
    {
        return $this->number() !== 0;
    }

    /**
     * Does the object is interactive ?
     *
     * @return bool
     */
    public function interactive(): bool
    {
        return ($this->data[7] & 2) >> 1 === 1;
    }
}
