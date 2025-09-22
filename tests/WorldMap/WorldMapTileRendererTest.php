<?php

namespace WorldMap;

use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\Coordinate\LatLongBounds;
use Arakne\MapParser\WorldMap\SwfWorldMap;
use Arakne\MapParser\WorldMap\WorldMapTileRenderer;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function imagepng;
use function unlink;

class WorldMapTileRendererTest extends TestCase
{
    use AssertImageTrait;

    #[Test]
    public function render_functional_full_size()
    {
        $renderer = new WorldMapTileRenderer(new SwfWorldMap(new SwfFile(__DIR__ . '/Fixtures/3.swf')));

        $this->assertSame(4, $renderer->maxZoom);

        for ($x = 2; $x <= 10; $x++) {
            for ($y = 1; $y <= 8; $y++) {
                $image = $renderer->renderOriginalSize($x, $y);

                $actual = __DIR__ . '/Fixtures/tiles/actual_' . $x . '-' . $y . '.png';
                imagepng($image, $actual);

                $this->assertImages(
                    __DIR__ . '/Fixtures/tiles/full_' . $x . '-' . $y . '.png',
                    $actual,
                );
                unlink($actual);
            }
        }
    }

    #[Test]
    public function render_zoom()
    {
        $renderer = new WorldMapTileRenderer(new SwfWorldMap(new SwfFile(__DIR__ . '/Fixtures/3.swf')));

        for ($zoom = 0; $zoom <= 5; ++$zoom) {
            $x = (int) ((6 / 16) * (2 ** $zoom));
            $y = (int) ((3 / 16) * (2 ** $zoom));

            $img = $renderer->render($x, $y, $zoom);
            $actual = __DIR__ . '/Fixtures/tiles/actual_' . $zoom . '.png';
            imagepng($img, $actual);

            $this->assertImages(
                __DIR__ . '/Fixtures/tiles/zoom_' . $zoom . '.png',
                $actual,
            );
            unlink($actual);
        }
    }

    #[Test]
    public function properties()
    {
        $renderer = new WorldMapTileRenderer(new SwfWorldMap(new SwfFile(__DIR__ . '/Fixtures/3.swf')));

        $this->assertEquals(new Bounds(-2, 1, -2, 3), $renderer->bounds);
        $this->assertSame(4, $renderer->maxZoom);
        $this->assertEquals(
            new Bounds(0, 0, 0, 0),
            $renderer->coordinate->toMapBounds(new LatLongBounds(
                -33.34350585937501,
                66.37275500247458,
                -23.087768554687504,
                67.676084581981
            ))
        );
    }
}
