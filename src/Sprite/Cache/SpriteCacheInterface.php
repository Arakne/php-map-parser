<?php

namespace Arakne\MapParser\Sprite\Cache;

use Arakne\MapParser\Sprite\Sprite;
use Closure;

interface SpriteCacheInterface
{
    /**
     * Map of exported sprite ID to SWF file path
     *
     * @param Closure():array<int, string> $compute Function to compute the exports if not already cached
     *
     * @return array<int, string>
     */
    public function exports(Closure $compute): array;

    /**
     * Get a sprite from the cache
     *
     * @param int $id Sprite ID
     * @param Closure(int):Sprite $compute Function to compute the sprite if not already cached. Takes the sprite ID as argument.
     *
     * @return Sprite
     */
    public function sprite(int $id, Closure $compute): Sprite;

    /**
     * Create a new instance with the given namespace for caching
     * This allows to separate caches for grounds and objects, while using the same cache backend.
     *
     * @param string $namespace The namespace key
     *
     * @return static The new cache instance
     */
    public function withNamespace(string $namespace): static;
}
