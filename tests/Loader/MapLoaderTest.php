<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

/**
 * Class SimpleMapLoaderTest
 */
class MapLoaderTest extends TestCase
{
    private MapLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new MapLoader();
    }

    #[Test]
    public function load()
    {
        $map = $this->loader->load(new MapStructure(10340, 15, 17, file_get_contents(__DIR__.'/../_files/10340.data')));

        $this->assertEquals(10340, $map->id);
        $this->assertEquals(15, $map->width);
        $this->assertEquals(17, $map->height);
        $this->assertCount(479, $map->cells);
        $this->assertContainsOnlyInstancesOf(Cell::class, $map->cells);
    }

    #[Test]
    public function test_load_encrypted()
    {
        $map = $this->loader->load(
            MapStructure::fromSwfFile(
                new SwfFile(__DIR__.'/../_files/10302_0709271842X.swf'),
                file_get_contents(__DIR__.'/../_files/10302.key')
            ),
        );

        $this->assertEquals(10302, $map->id);
        $this->assertEquals(15, $map->width);
        $this->assertEquals(17, $map->height);
        $this->assertCount(479, $map->cells);
        $this->assertContainsOnlyInstancesOf(Cell::class, $map->cells);
    }
}
