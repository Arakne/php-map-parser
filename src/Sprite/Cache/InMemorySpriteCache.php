<?php

namespace Arakne\MapParser\Sprite\Cache;

use Arakne\MapParser\Sprite\Sprite;
use Arakne\MapParser\Util\ClockCache;
use Closure;
use Override;

/**
 * Simple in-memory implementation of {@see SpriteCacheInterface} using a clock cache for sprites.
 */
final class InMemorySpriteCache implements SpriteCacheInterface
{
    /**
     * @var array<int, string>|null
     */
    private ?array $exports = null;

    /**
     * @var ClockCache<int, Sprite>
     */
    private ClockCache $cache;

    /**
     * @param positive-int $capacity Maximum number of sprites to keep in cache
     */
    public function __construct(int $capacity = 100)
    {
        $this->cache = new ClockCache($capacity);
    }

    #[Override]
    public function exports(Closure $compute): array
    {
        return $this->exports ??= $compute();
    }

    #[Override]
    public function sprite(int $id, Closure $compute): Sprite
    {
        return $this->cache[$id] ??= $compute($id);
    }

    #[Override]
    public function withNamespace(string $namespace): static
    {
        return new self();
    }
}
