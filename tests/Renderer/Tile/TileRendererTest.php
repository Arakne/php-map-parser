<?php

namespace Renderer\Tile;

use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\MapRendererInterface;
use Arakne\MapParser\Renderer\Tile\FilesystemTileCache;
use Arakne\MapParser\Renderer\Tile\MapCoordinates;
use Arakne\MapParser\Renderer\Tile\TileRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function explode;
use function file_get_contents;
use function glob;
use function imagepng;
use function max;
use function min;
use function tempnam;
use function unlink;

class TileRendererTest extends TestCase
{
    use AssertImageTrait;

    public const array MAPS = [
        '4,4' => 10332,
        '5,4' => 10319,
        '5,5' => 10334,
        '4,5' => 10333,
    ];

    #[Test]
    public function toMapCoordinates()
    {
        $renderer = new TileRenderer(
            $this->createMock(MapRendererInterface::class),
            fn ($coords) => null,
            Xmin: -10,
            Xmax: 0,
            Ymin: 5,
            Ymax: 10,
        );

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(0, 0));

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 256,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(1, 0));

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 512,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new MapCoordinates(
                x: -9,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 230,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(2, 0));

        $this->assertEquals([
            new MapCoordinates(
                x: -9,
                y: 5,
                xSourceOffset: 26,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(3, 0));

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 256,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new MapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 176,
            ),
        ], $renderer->toMapCoordinates(0, 1));

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 0,
                ySourceOffset: 80,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(0, 2));

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 256,
                ySourceOffset: 256,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new MapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 256,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 176,
            ),
        ], $renderer->toMapCoordinates(1, 1));

        $this->assertEquals([
            new MapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 512,
                ySourceOffset: 256,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new MapCoordinates(
                x: -9,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 256,
                xDestinationOffset: 230,
                yDestinationOffset: 0,
            ),
            new MapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 512,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 176,
            ),
            new MapCoordinates(
                x: -9,
                y: 6,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 230,
                yDestinationOffset: 176,
            ),
        ], $renderer->toMapCoordinates(2, 1));
    }

    #[Test]
    public function renderOriginalSize_functional()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (MapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
        );

        for ($x = 0; $x < 6; $x++) {
            for ($y = 0; $y < 4; $y++) {
                $img = $renderer->renderOriginalSize($x, $y);
                imagepng($img, $path = __DIR__ . '/_files/actual_' . $x . '_' . $y . '.png');
                $this->assertImages(__DIR__ . '/_files/' . $x . '_' . $y . '.png', $path);
                unlink($path);
            }
        }
    }

    #[Test]
    public function render_max_zoom_functional()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (MapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
        );

        $this->assertSame(3, $renderer->maxZoom);

        for ($x = 0; $x < 6; $x++) {
            for ($y = 0; $y < 4; $y++) {
                $img = $renderer->render($x, $y, $renderer->maxZoom);
                imagepng($img, $path = __DIR__ . '/_files/actual_' . $x . '_' . $y . '.png');
                $this->assertImages(__DIR__ . '/_files/' . $x . '_' . $y . '.png', $path);
                unlink($path);
            }
        }
    }

    #[Test]
    public function render_with_zoom()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (MapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
        );

        for ($zoom = 0; $zoom <= 8; $zoom++) {
            $img = $renderer->render(0, 0, $zoom);
            imagepng($img, $path = __DIR__ . '/_files/actual_' . $zoom . '.png');
            $this->assertImages(__DIR__ . '/_files/zoom_' . $zoom . '.png', $path);
            unlink($path);
        }
    }

    #[Test]
    public function render_with_scale()
    {
        $mapRenderer = new MapRenderer(
            new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
            new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
        );

        foreach ([1.1, 1.3, 1.5] as $scale) {
            $renderer = new TileRenderer(
                $mapRenderer,
                function (MapCoordinates $coords) {
                    if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                        return null;
                    }

                    return MapStructure::fromSwfFile(
                        new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                        file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                    );
                },
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                scale: $scale,
            );

            $img = $renderer->render(1, 1, 2);
            imagepng($img, $path = __DIR__ . '/_files/actual_scale_' . $scale . '.png');
            $this->assertImages(
                __DIR__ . '/_files/scale_' . $scale . '.png',
                $path
            );
            unlink($path);
        }
    }

    #[Test]
    public function functional_with_filesystem_cache()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (MapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
            min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            cache: new FilesystemTileCache($path = '/tmp/' . bin2hex(random_bytes(5))),
        );

        $img = $renderer->render(1, 1, 2);
        imagepng($img, $actual = tempnam('/tmp', 'tile_'));

        $this->assertImages($actual, $path . '/zoom/2/1_1.png');
        $this->assertEqualsCanonicalizing([
            $path . '/tiles/2_2.png',
            $path . '/tiles/2_3.png',
            $path . '/tiles/3_2.png',
            $path . '/tiles/3_3.png',
        ], glob($path . '/tiles/*.png'));
        $this->assertEqualsCanonicalizing([
            $path . '/maps/10333.png',
            $path . '/maps/10334.png',
        ], glob($path . '/maps/*.png'));

        $cached = $renderer->render(1, 1, 2);
        imagepng($cached, $cachedPath = tempnam('/tmp', 'tile_'));

        $this->assertImages($actual, $cachedPath);
    }
}
