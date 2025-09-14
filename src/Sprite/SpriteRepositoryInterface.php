<?php

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
