<?php

namespace Util;

use Arakne\MapParser\Util\ClockCache;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function var_dump;

class ClockCacheTest extends TestCase
{
    #[Test]
    public function shouldNotStoreMoreThanCapacity()
    {
        $cache = new ClockCache(3);

        $cache['a'] = 1;
        $cache['b'] = 2;
        $cache['c'] = 3;
        $this->assertCount(3, $cache);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], iterator_to_array($cache));

        $cache['d'] = 4; // This should evict 'a'
        $this->assertCount(3, $cache);
        $this->assertArrayNotHasKey('a', $cache);
        $this->assertSame(['b' => 2, 'c' => 3, 'd' => 4], iterator_to_array($cache));
    }

    #[Test]
    public function shouldNotEvictLastAccessedItem()
    {
        $cache = new ClockCache(3);
        $cache['a'] = 1;
        $cache['b'] = 2;
        $cache['c'] = 3;
        $cache['d'] = 4;

        $this->assertSame(2, $cache['b']);

        $cache['e'] = 5;
        $this->assertCount(3, $cache);
        $this->assertArrayNotHasKey('c', $cache);
        $this->assertSame(['b' => 2, 'd' => 4, 'e' => 5], iterator_to_array($cache));
    }

    #[Test]
    public function unsetShouldFreeASlot()
    {
        $cache = new ClockCache(3);
        $cache['a'] = 1;
        $cache['b'] = 2;
        $cache['c'] = 3;

        unset($cache['b']);
        $this->assertCount(2, $cache);

        $cache['d'] = 4;
        $this->assertCount(3, $cache);
        $this->assertSame(['a' => 1, 'c' => 3, 'd' => 4], iterator_to_array($cache));
    }

    #[Test]
    public function reuseKeyShouldReuseSlot()
    {
        $cache = new ClockCache(3);
        $cache['a'] = 1;
        $cache['b'] = 2;
        $cache['c'] = 3;

        $cache['b'] = 4;
        $this->assertCount(3, $cache);
        $this->assertSame(['a' => 1, 'b' => 4, 'c' => 3], iterator_to_array($cache));
    }

    #[Test]
    public function reusedKeyShouldNotBeEvicted()
    {
        $cache = new ClockCache(3);
        $cache['a'] = 1;
        $cache['b'] = 2;
        $cache['c'] = 3;
        $cache['d'] = 4;

        $cache['b'] = 5;
        $cache['e'] = 6;

        $this->assertCount(3, $cache);
        $this->assertSame(['b' => 5, 'd' => 4, 'e' => 6], iterator_to_array($cache));
    }

    #[Test]
    public function shouldKeepAccessedItems()
    {
        $cache = new ClockCache(10);

        $cache['foo'] = 1;
        $cache['bar'] = 2;
        $cache['baz'] = 3;

        for ($i = 0; $i < 100; $i++) {
            $cache['item' . $i] = $i;
            $this->assertSame(2, $cache['bar']);
            $this->assertSame(3, $cache['baz']);
        }

        $this->assertCount(10, $cache);
        $this->assertSame(2, $cache['bar']);
        $this->assertSame(3, $cache['baz']);
    }

    #[Test]
    public function unsetNonExistentKeyShouldDoNothing()
    {
        $cache = new ClockCache(3);
        $cache['foo'] = 1;
        $cache['bar'] = 2;
        $cache['baz'] = 3;


        unset($cache['non_existent_key']);
        $this->assertCount(3, $cache);
        $this->assertSame(['foo' => 1, 'bar' => 2, 'baz' => 3], iterator_to_array($cache));
    }

    #[Test]
    public function accessNonExistentKeyShouldReturnNull()
    {
        $cache = new ClockCache(3);
        $cache['foo'] = 1;
        $cache['bar'] = 2;
        $cache['baz'] = 3;

        $this->assertNull($cache['non_existent_key']);
    }

    #[Test]
    public function arrayAppendNotSupported()
    {
        $this->expectException(InvalidArgumentException::class);

        $cache = new ClockCache(3);
        $cache[] = 1;
    }
}
