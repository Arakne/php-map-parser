<?php

namespace WorldMap;

use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Util\Bounds;
use Arakne\MapParser\WorldMap\SwfWorldMap;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function tempnam;

class SwfWorldMapTest extends TestCase
{
    use AssertImageTrait;

    private SwfWorldMap $worldMap;

    protected function setUp(): void
    {
        $this->worldMap = new SwfWorldMap(new SwfFile(__DIR__ . '/Fixtures/3.swf'));
    }

    #[Test]
    public function bounds()
    {
        $this->assertEquals(new Bounds(
            xMin: -2,
            xMax: 1,
            yMin: -2,
            yMax: 3,
        ), $this->worldMap->bounds());

        $this->assertSame(
            $this->worldMap->bounds(),
            $this->worldMap->bounds(),
        );
    }

    #[Test]
    public function chunkSuccess()
    {
        $actual = tempnam('/tmp', 'chunk');
        file_put_contents($actual, $this->worldMap->chunk(0, 0));

        $this->assertImages(
            __DIR__ . '/Fixtures/chunk/0_0.png',
            $actual,
        );
    }

    #[Test]
    public function chunkNotFound()
    {
        $this->assertNull($this->worldMap->chunk(5, -2));
    }
}
