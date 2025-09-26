<?php

namespace Sprite\Cache;

use Arakne\MapParser\Sprite\Cache\InMemorySpriteCache;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function glob;

class InMemorySpriteCacheTest extends TestCase
{
    private SwfSpriteRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf'), new InMemorySpriteCache(1));
    }

    #[Test]
    public function spriteIsCached()
    {
        $cache = new InMemorySpriteCache(2);
        $sprite1 = $cache->sprite(7019, fn($id) => $this->repository->get($id));
        $sprite2 = $cache->sprite(7019, fn($id) => $this->repository->get($id));
        $this->assertSame($sprite1, $sprite2, 'Le sprite doit être mis en cache et retourné identique');
        $this->assertTrue($sprite1->valid, 'Le sprite doit être valide');
    }

    #[Test]
    public function exportsIsComputedOnce()
    {
        $cache = new InMemorySpriteCache();
        $calls = 0;
        $compute = function() use (&$calls) {
            $calls++;
            return ["foo" => "bar"];
        };
        $exports1 = $cache->exports($compute);
        $exports2 = $cache->exports($compute);
        $this->assertSame($exports1, $exports2);
        $this->assertEquals(1, $calls, 'La closure doit être appelée une seule fois');
    }

    #[Test]
    public function withNamespaceReturnsNewInstance()
    {
        $cache = new InMemorySpriteCache();
        $cache->sprite(7019, fn($id) => $this->repository->get($id));
        $newCache = $cache->withNamespace('test');
        $this->assertNotSame($cache, $newCache);
        $sprite = $newCache->sprite(7019, fn($id) => $this->repository->get($id));
        $this->assertInstanceOf('Arakne\\MapParser\\Sprite\\Sprite', $sprite, 'La nouvelle instance doit retourner un Sprite');
        $this->assertTrue($sprite->valid, 'Le sprite doit être valide');
    }

    #[Test]
    public function cacheEviction()
    {
        $cache = new InMemorySpriteCache(2);
        $sprite1 = $cache->sprite(7019, fn($id) => $this->repository->get($id));
        $sprite2 = $cache->sprite(7020, fn($id) => $this->repository->get($id));
        $sprite3 = $cache->sprite(7021, fn($id) => $this->repository->get($id));

        $sprite1bis = $cache->sprite(7019, fn($id) => $this->repository->get($id));
        $this->assertNotSame($sprite1, $sprite1bis, 'Le sprite doit être recalculé après éviction');
        $this->assertTrue($sprite1bis->valid, 'Le sprite recalculé doit être valide');
    }
}
