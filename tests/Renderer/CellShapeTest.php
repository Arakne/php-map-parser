<?php

namespace Renderer;

use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Tile\Coordinate\Point;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

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
        $mapStructure = MapStructure::fromSwfFile(new SwfFile($swf));
        $map = new MapLoader()->load($mapStructure, MapKey::fromFile($key));

        $allShapes = CellShape::fromMap($map, false);

        foreach ($map->cells as $cellId => $cell) {
            $cellShape = CellShape::fromCellId($map, $cellId);

            $this->assertEquals($allShapes[$cellId], $cellShape);
        }
    }

    #[Test]
    public function fromCellIdXY()
    {
        $mapStructure = MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../_files/10302_0709271842X.swf'));
        $map = new MapLoader()->load($mapStructure, MapKey::fromFile(__DIR__ . '/../_files/10302.key'));

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

    #[Test]
    public function toDisplayPositionOnMapWithDefaultSizeShouldBeIdenticalToXY()
    {
        $mapStructure = MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../_files/10302_0709271842X.swf'));
        $map = new MapLoader()->load($mapStructure, MapKey::fromFile(__DIR__ . '/../_files/10302.key'));

        $cells = CellShape::fromMap($map);

        foreach ($cells as $cellId => $cell) {
            $this->assertSame($cell->x, $cell->toDisplayPosition()->x, "Cell $cellId x");
            $this->assertSame($cell->y, $cell->toDisplayPosition()->y, "Cell $cellId y");
        }
    }

    #[Test]
    public function toDisplayPositionOnBiggerMap()
    {
        $mapStructure = MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../_files/4208_0706131721X.swf'));
        $map = new MapLoader()->load($mapStructure, MapKey::fromFile(__DIR__ . '/../_files/4208.key'));

        $cells = CellShape::fromMap($map, false);

        $this->assertNull($cells[0]->toDisplayPosition());
        $this->assertNull($cells[777]->toDisplayPosition());
        $this->assertNull($cells[780]->toDisplayPosition());

        $this->assertNotEquals($cells[430]->x, $cells[430]->toDisplayPosition()->x);
        $this->assertNotEquals($cells[430]->y, $cells[430]->toDisplayPosition()->y);
        $this->assertEquals(new Point(185, 237), $cells[430]->toDisplayPosition());
    }

    #[Test]
    public function toDisplayPositionOnSmallerMap()
    {
        $mapStructure = MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../_files/703_0706131721X.swf'));
        $map = new MapLoader()->load($mapStructure, MapKey::fromFile(__DIR__ . '/../_files/703.key'));

        $cells = CellShape::fromMap($map, false);

        $this->assertNotEquals($cells[56]->x, $cells[56]->toDisplayPosition()->x);
        $this->assertNotEquals($cells[56]->y, $cells[56]->toDisplayPosition()->y);
        $this->assertEquals(new Point(370, 229), $cells[56]->toDisplayPosition());
    }
}
