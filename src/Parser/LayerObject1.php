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
        private readonly array $data,
    ) {}
}
