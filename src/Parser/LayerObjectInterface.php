<?php

namespace Arakne\MapParser\Parser;

/**
 * Base type for an object layer
 */
interface LayerObjectInterface
{
    /**
     * The object number on the cell
     */
    public int $number { get; }

    /**
     * The rotation value of the sprite
     */
    public int $rotation { get; }

    /**
     * Does the sprite has been flipped ?
     */
    public bool $flip { get; }

    /**
     * Check if the layer is active (i.e. has an object)
     */
    public bool $active { get; }
}
