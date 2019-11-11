<?php

namespace Arakne\MapParser\Parser;

/**
 * Base type for an object layer
 */
interface LayerObjectInterface
{
    /**
     * The object number on the cell
     *
     * @return int
     */
    public function number(): int;

    /**
     * The rotation value of the sprite
     *
     * @return int
     */
    public function rotation(): int;

    /**
     * Does the sprite has been flipped ?
     *
     * @return bool
     */
    public function flip(): bool;
}
