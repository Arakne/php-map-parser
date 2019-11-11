<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;
use PHPUnit\Framework\TestCase;

/**
 * Class SimpleMapLoaderTest
 */
class SimpleMapLoaderTest extends TestCase
{
    /**
     * @var SimpleMapLoader
     */
    private $loader;

    protected function setUp()
    {
        $this->loader = new SimpleMapLoader();
    }

    /**
     *
     */
    public function test_load()
    {
        $map = $this->loader->load(10340, 15, 17, file_get_contents(__DIR__.'/../_files/10340.data'));

        $this->assertEquals(10340, $map->id());
        $this->assertEquals(15, $map->width());
        $this->assertEquals(17, $map->height());
        $this->assertCount(479, $map->cells());
        $this->assertContainsOnly(Cell::class, $map->cells());
    }
}
