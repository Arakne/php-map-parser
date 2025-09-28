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
     *
     * Note: The slope 0 is not valid, flat ground is represented by slope 1
     *
     * @var int<0, 15>
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
