<?php

namespace Sprite\Cache;

use Arakne\MapParser\Sprite\Cache\SqliteSpriteCache;
use Arakne\MapParser\Sprite\Sprite;
use Arakne\MapParser\Sprite\SpriteState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SqliteSpriteCacheTest extends TestCase
{
    private string $dbPath;
    private SqliteSpriteCache $cache;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/sqlite_sprite_cache_test.db';
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        $this->cache = new SqliteSpriteCache($this->dbPath, '', 3);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    #[Test]
    public function spriteIsPersisted()
    {
        $sprite = $this->makeSprite(42);
        $result = $this->cache->sprite(42, fn($id) => $sprite);
        $this->assertEquals($sprite, $result);

        // Nouvelle instance sur le même fichier
        $newCache = new SqliteSpriteCache($this->dbPath, '');
        $result2 = $newCache->sprite(42, fn($id) => $this->makeSprite($id, 'new'));
        $this->assertEquals($sprite, $result2, 'Le sprite doit être retrouvé depuis la base');
    }

    #[Test]
    public function exportsIsPersisted()
    {
        $compute = fn() => [1 => 'file1.swf', 2 => 'file2.swf'];
        $exports1 = $this->cache->exports($compute);
        $exports2 = $this->cache->exports(fn() => [3 => 'file3.swf']);
        $this->assertSame($exports1, $exports2, 'Les exports doivent être persistés et non recalculés');

        // Nouvelle instance sur le même fichier
        $newCache = new SqliteSpriteCache($this->dbPath, '');
        $exports3 = $newCache->exports(fn() => [4 => 'file4.swf']);
        $this->assertSame($exports1, $exports3, 'Les exports doivent être retrouvés depuis la base');
    }

    #[Test]
    public function withNamespaceIsIsolated()
    {
        $sprite = $this->makeSprite(99);
        $cache2 = $this->cache->withNamespace('other');
        $result = $cache2->sprite(99, fn($id) => $sprite);
        $this->assertEquals($sprite, $result);

        // Le cache principal ne doit pas retrouver le sprite du namespace 'other'
        $result2 = $this->cache->sprite(99, fn($id) => $this->makeSprite($id, 'main'));
        $this->assertNotEquals($sprite, $result2, 'Le sprite doit être isolé par namespace');
    }

    #[Test]
    public function inMemoryCacheIdentity()
    {
        $cache = new SqliteSpriteCache($this->dbPath, '', 3);
        $sprite1 = $cache->sprite(1, fn($id) => $this->makeSprite($id));
        $sprite2 = $cache->sprite(2, fn($id) => $this->makeSprite($id));
        $sprite3 = $cache->sprite(3, fn($id) => $this->makeSprite($id));

        $sprite1_bis = $cache->sprite(1, fn($id) => $this->makeSprite($id, 'bis'));
        $sprite2_bis = $cache->sprite(2, fn($id) => $this->makeSprite($id, 'bis'));
        $sprite3_bis = $cache->sprite(3, fn($id) => $this->makeSprite($id, 'bis'));

        $this->assertSame($sprite1, $sprite1_bis, 'L’instance du sprite 1 doit être la même tant que la capacité n’est pas dépassée');
        $this->assertSame($sprite2, $sprite2_bis, 'L’instance du sprite 2 doit être la même tant que la capacité n’est pas dépassée');
        $this->assertSame($sprite3, $sprite3_bis, 'L’instance du sprite 3 doit être la même tant que la capacité n’est pas dépassée');

        $sprite4 = $cache->sprite(4, fn($id) => $this->makeSprite($id));

        $this->assertSame($sprite4, $cache->sprite(4, fn($id) => $this->makeSprite($id, 'bis')));
        $this->assertNotSame($sprite1, $cache->sprite(1, fn($id) => $this->makeSprite($id, 'bis')));
    }

    private function makeSprite(int $id, string $suffix = ''): Sprite
    {
        return new Sprite(
            id: $id,
            pngData: 'PNGDATA' . $suffix,
            width: 10.0,
            height: 20.0,
            offsetX: 1.0,
            offsetY: 2.0,
            state: SpriteState::Valid,
        );
    }
}
