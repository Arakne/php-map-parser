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
     * Multiply by 90 to get the angle in degrees
     *
     * @var int<0, 3>
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
