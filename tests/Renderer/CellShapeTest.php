<?php

namespace Renderer;

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class CellShapeTest extends TestCase
{
    #[
        Test,
        TestWith([__DIR__ . '/../_files/10302_0709271842X.swf', __DIR__ . '/../_files/10302.key'], name: 'simple'),
        TestWith([__DIR__ . '/../_files/703_0706131721X.swf', __DIR__ . '/../_files/703.key'], name: 'small'),
        TestWith([__DIR__ . '/../_files/4208_0706131721X.swf', __DIR__ . '/../_files/4208.key'], name: 'bif'),
    ]
    public function fromCellIdShouldBehaveSameAsFromMap(string $swf, string $key)
    {
        $mapStructure = MapStructure::fromSwfFile(new SwfFile($swf), file_get_contents($key));
        $map = new MapLoader()->load($mapStructure);

        $allShapes = CellShape::fromMap($map, false);

        foreach ($map->cells as $cellId => $cell) {
            $cellShape = CellShape::fromCellId($map, $cellId);

            $this->assertEquals($allShapes[$cellId], $cellShape);
        }
    }

    #[Test]
    public function fromCellIdXY()
    {
        $mapStructure = MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../_files/10302_0709271842X.swf'), file_get_contents(__DIR__ . '/../_files/10302.key'));
        $map = new MapLoader()->load($mapStructure);

        $this->assertSame(0, CellShape::fromCellId($map, 0)->x);
        $this->assertSame(0, CellShape::fromCellId($map, 0)->y);

        $this->assertSame(53, CellShape::fromCellId($map, 1)->x);
        $this->assertSame(0, CellShape::fromCellId($map, 1)->y);

        $this->assertSame(159, CellShape::fromCellId($map, 3)->x);
        $this->assertSame(0, CellShape::fromCellId($map, 3)->y);

        $this->assertSame(742, CellShape::fromCellId($map, 14)->x);
        $this->assertSame(0, CellShape::fromCellId($map, 14)->y);

        $this->assertSame(26, CellShape::fromCellId($map, 15)->x);
        $this->assertSame(13, CellShape::fromCellId($map, 15)->y);

        $this->assertSame(715, CellShape::fromCellId($map, 28)->x);
        $this->assertSame(13, CellShape::fromCellId($map, 28)->y);

        $this->assertSame(583, CellShape::fromCellId($map, 388)->x);
        $this->assertSame(351, CellShape::fromCellId($map, 388)->y);
    }
}
