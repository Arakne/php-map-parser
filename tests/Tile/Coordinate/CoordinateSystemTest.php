<?php

namespace Tile\Coordinate;

use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Tile\BaseTileRenderer;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\Coordinate\CoordinateSystem;
use Arakne\MapParser\Tile\Coordinate\LatLongBounds;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class CoordinateSystemTest extends TestCase
{
    #[Test]
    public function toMapBounds()
    {
        $system = new CoordinateSystem(
            new Bounds(
                -30,
                29,
                -30,
                59,
            ),
            BaseTileRenderer::TILE_SIZE,
            8,
            MapRenderer::DISPLAY_WIDTH,
            MapRenderer::DISPLAY_HEIGHT,
            16 / 15
        );

        $bbox = new LatLongBounds(
            -33.34350585937501,
            66.37275500247458,
            -23.087768554687504,
            67.676084581981
        );

        $bounds = $system->toMapBounds($bbox);

        $this->assertSame(3, $bounds->xMin);
        $this->assertSame(6, $bounds->xMax);
        $this->assertSame(4, $bounds->yMin);
        $this->assertSame(5, $bounds->yMax);
    }

    #[Test]
    public function cellToLatLong()
    {
        $system = new CoordinateSystem(
            new Bounds(
                -30,
                29,
                -30,
                59,
            ),
            BaseTileRenderer::TILE_SIZE,
            8,
            MapRenderer::DISPLAY_WIDTH,
            MapRenderer::DISPLAY_HEIGHT,
            16 / 15
        );

        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(
                new SwfFile(__DIR__ . '/../../_files/10332_0706131721X.swf'),
                file_get_contents(__DIR__ . '/../../_files/10332.key'),
            ),
            new MapCoordinates(4, 4),
        );

        $latLong = $system->cellToLatLong($map, 42);

        $this->assertEqualsWithDelta(67.9746, $latLong->latitude, 0.0001);
        $this->assertEqualsWithDelta(-28.1414, $latLong->longitude, 0.0001);
    }

    #[Test]
    public function cellToLatLongOutOfBounds()
    {
        $system = new CoordinateSystem(
            new Bounds(
                -30,
                29,
                -30,
                59,
            ),
            BaseTileRenderer::TILE_SIZE,
            8,
            MapRenderer::DISPLAY_WIDTH,
            MapRenderer::DISPLAY_HEIGHT,
            16 / 15
        );

        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(
                new SwfFile(__DIR__ . '/../../_files/10332_0706131721X.swf'),
                file_get_contents(__DIR__ . '/../../_files/10332.key'),
            ),
            new MapCoordinates(150, -40),
        );

        $this->assertNull($system->cellToLatLong($map, 42));
    }

    #[Test]
    public function cellToLatInvalidCell()
    {
        $system = new CoordinateSystem(
            new Bounds(
                -30,
                29,
                -30,
                59,
            ),
            BaseTileRenderer::TILE_SIZE,
            8,
            MapRenderer::DISPLAY_WIDTH,
            MapRenderer::DISPLAY_HEIGHT,
            16 / 15
        );

        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(
                new SwfFile(__DIR__ . '/../../_files/10332_0706131721X.swf'),
                file_get_contents(__DIR__ . '/../../_files/10332.key'),
            ),
            new MapCoordinates(4, 4),
        );

        $this->assertNull($system->cellToLatLong($map, 44554));
    }
}
