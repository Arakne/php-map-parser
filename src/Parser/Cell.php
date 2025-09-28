<?php

/*
 * This file is part of PHP Map parser.
 *
 * PHP Map parser is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * PHP Map parser is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with PHP Map parser.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2019-2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

namespace Arakne\MapParser\Parser;

use function assert;
use function count;
use function in_array;

/**
 * Parsed cell data
 *
 * @see https://github.com/Emudofus/Dofus/blob/1.29/ank/battlefield/utils/Compressor.as#L54
 */
final class Cell
{
    public const array TELEPORT_OBJECT_NUMBERS = [1029, 1030];

    /**
     * Check if the cell do not block the line of sight
     */
    public bool $lineOfSight {
        get => ($this->data[0] & 1) === 1;
    }

    /**
     * Get the permitted movement type
     *
     * The value is an int in range [0 - 7] :
     *
     * - 0 means not walkable
     * - 1 is used by interactive objects (the client considers them as walkable, so the cell is clickable, but the server does not allow movement on it)
     * - 2 to 7 means different levels of walkable cells. Bigger is the movement, lower is the weight on pathing
     *
     * @var int<0, 7>
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
     * Does the cell contain a teleport object?
     * True if this cell can be used to change map, false otherwise.
     */
    public bool $isTeleport {
        get => in_array($this->layer1->number, self::TELEPORT_OBJECT_NUMBERS, true) || in_array($this->layer2->number, self::TELEPORT_OBJECT_NUMBERS, true);
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
