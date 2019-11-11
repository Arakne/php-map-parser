<?php


namespace Arakne\MapParser\Parser;

/**
 * The ground layer object
 */
final class GroundObject implements LayerObjectInterface
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
        return (($this->data[0] & 24) << 6) + (($this->data[2] & 7) << 6) + $this->data[3];
    }

    /**
     * {@inheritdoc}
     */
    public function rotation(): int
    {
        return ($this->data[1] & 48) >> 4;
    }

    /**
     * {@inheritdoc}
     */
    public function flip(): bool
    {
        return ($this->data[4] & 2) >> 1 === 1;
    }

    /**
     * Get the ground elevation level
     *
     * @return int
     */
    public function level(): int
    {
        return $this->data[1] & 15;
    }

    /**
     * Get the ground slope
     *
     * @return int
     */
    public function slope(): int
    {
        return ($this->data[4] & 60) >> 2;
    }
}
