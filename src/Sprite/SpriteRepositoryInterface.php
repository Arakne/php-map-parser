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

namespace Arakne\MapParser\Sprite;

/**
 * Base type for loading map sprites
 */
interface SpriteRepositoryInterface
{
    /**
     * Loads a sprite by its ID
     *
     * This method will always return a Sprite object, even if the sprite is missing or invalid.
     * To check if the sprite is valid, use {@see Sprite::$valid}, or {@see Sprite::$state} to get the exact state.
     *
     * This method may throw exceptions in case of corrupted SWF files or other unexpected errors,
     * but it should not throw exceptions for missing or invalid (empty) sprites.
     *
     * @param int $id Sprite ID
     * @return Sprite The sprite
     */
    public function get(int $id): Sprite;
}
