<?php

namespace WorldMap;

use Arakne\MapParser\Test\AssertImageTrait;
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
}
