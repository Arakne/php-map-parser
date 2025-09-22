<?php

namespace Renderer\Tile;

use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\MapRendererInterface;
use Arakne\MapParser\Renderer\TileRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Tile\Cache\FilesystemTileCache;
use Arakne\MapParser\Tile\Cache\SqliteCache;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\TileMapCoordinates;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function explode;
use function file_get_contents;
use function glob;
use function imagepng;
use function imagesavealpha;
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
            new Bounds(
                xMin: -10,
                xMax: 0,
                yMin: 5,
                yMax: 10,
            ),
        );

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(0, 0));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 256,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(1, 0));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 512,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new TileMapCoordinates(
                x: -9,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 230,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(2, 0));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -9,
                y: 5,
                xSourceOffset: 26,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(3, 0));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 256,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new TileMapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 0,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 176,
            ),
        ], $renderer->toMapCoordinates(0, 1));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 0,
                ySourceOffset: 80,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
        ], $renderer->toMapCoordinates(0, 2));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 256,
                ySourceOffset: 256,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new TileMapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 256,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 176,
            ),
        ], $renderer->toMapCoordinates(1, 1));

        $this->assertEquals([
            new TileMapCoordinates(
                x: -10,
                y: 5,
                xSourceOffset: 512,
                ySourceOffset: 256,
                xDestinationOffset: 0,
                yDestinationOffset: 0,
            ),
            new TileMapCoordinates(
                x: -9,
                y: 5,
                xSourceOffset: 0,
                ySourceOffset: 256,
                xDestinationOffset: 230,
                yDestinationOffset: 0,
            ),
            new TileMapCoordinates(
                x: -10,
                y: 6,
                xSourceOffset: 512,
                ySourceOffset: 0,
                xDestinationOffset: 0,
                yDestinationOffset: 176,
            ),
            new TileMapCoordinates(
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
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
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
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
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
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
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
                function (TileMapCoordinates $coords) {
                    if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                        return null;
                    }

                    return MapStructure::fromSwfFile(
                        new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                        file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                    );
                },
                new Bounds(
                    min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                    max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                    min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                    max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                ),
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
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
            cache: new FilesystemTileCache($path = '/tmp/' . bin2hex(random_bytes(5))),
        );

        $img = $renderer->render(1, 1, 2);
        imagesavealpha($img, true);
        imagepng($img, $actual = tempnam('/tmp', 'tile_'));

        $this->assertImages($actual, $path . '/zoom/2/1_1.png');
        $this->assertEqualsCanonicalizing([
            $path . '/tiles/2_2.png',
            $path . '/tiles/2_3.png',
            $path . '/tiles/3_2.png',
            $path . '/tiles/3_3.png',
        ], glob($path . '/tiles/*.png'));
        $this->assertEqualsCanonicalizing([
            $path . '/maps/4_5.png',
            $path . '/maps/5_5.png',
        ], glob($path . '/maps/*.png'));

        $cached = $renderer->render(1, 1, 2);
        imagesavealpha($cached, true);
        imagepng($cached, $cachedPath = tempnam('/tmp', 'tile_'));

        $this->assertImages($actual, $cachedPath);
    }

    #[Test]
    public function functional_with_sqlite_cache()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
            cache: new SqliteCache('/tmp/' . bin2hex(random_bytes(5))),
        );

        $img = $renderer->render(1, 1, 2);
        imagesavealpha($img, true);
        imagepng($img, $actual = tempnam('/tmp', 'tile_'));

        $cached = $renderer->render(1, 1, 2);
        imagesavealpha($cached, true);
        imagepng($cached, $cachedPath = tempnam('/tmp', 'tile_'));

        $this->assertImages($actual, $cachedPath);
    }

    #[Test]
    public function warmup()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
            cache: new FilesystemTileCache($path = '/tmp/' . bin2hex(random_bytes(5))),
        );

        $logs = [];
        $renderer->warmup(
            function ($level, $current, $total) use (&$logs) {
                $logs[] = [$level, $current, $total];
            },
        );

        $this->assertSame([
            ['maps', 1, 4],
            ['maps', 2, 4],
            ['maps', 3, 4],
            ['maps', 4, 4],
            ...array_map(
                fn ($i) => ['tiles', $i, 64],
                range(1, 64)
            ),
            ['zoom 0', 1, 1],
            ['zoom 1', 1, 4],
            ['zoom 1', 2, 4],
            ['zoom 1', 3, 4],
            ['zoom 1', 4, 4],
            ['zoom 2', 1, 16],
            ['zoom 2', 2, 16],
            ['zoom 2', 3, 16],
            ['zoom 2', 4, 16],
            ['zoom 2', 5, 16],
            ['zoom 2', 6, 16],
            ['zoom 2', 7, 16],
            ['zoom 2', 8, 16],
            ['zoom 2', 9, 16],
            ['zoom 2', 10, 16],
            ['zoom 2', 11, 16],
            ['zoom 2', 12, 16],
            ['zoom 2', 13, 16],
            ['zoom 2', 14, 16],
            ['zoom 2', 15, 16],
            ['zoom 2', 16, 16],
        ], $logs);

        $this->assertCount(64, glob($path . '/tiles/*.png'));
        $this->assertEqualsCanonicalizing([
            $path . '/maps/4_4.png',
            $path . '/maps/4_5.png',
            $path . '/maps/5_4.png',
            $path . '/maps/5_5.png',
        ], glob($path . '/maps/*.png'));
        $this->assertEqualsCanonicalizing([
            $path . '/zoom/0/0_0.png',
        ], glob($path . '/zoom/0/*.png'));
        $this->assertEqualsCanonicalizing([
            $path . '/zoom/1/0_0.png',
            $path . '/zoom/1/0_1.png',
            $path . '/zoom/1/1_0.png',
            $path . '/zoom/1/1_1.png',
        ], glob($path . '/zoom/1/*.png'));
        $this->assertCount(16, glob($path . '/zoom/2/*.png'));
    }

    #[Test]
    public function warmupWithMinZoom()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
            cache: new FilesystemTileCache($path = '/tmp/' . bin2hex(random_bytes(5))),
        );

        $logs = [];
        $renderer->warmup(
            function ($level, $current, $total) use (&$logs) {
                $logs[] = [$level, $current, $total];
            },
            2
        );

        $this->assertSame([
            ['maps', 1, 4],
            ['maps', 2, 4],
            ['maps', 3, 4],
            ['maps', 4, 4],
            ...array_map(
                fn ($i) => ['tiles', $i, 64],
                range(1, 64)
            ),
            ['zoom 2', 1, 16],
            ['zoom 2', 2, 16],
            ['zoom 2', 3, 16],
            ['zoom 2', 4, 16],
            ['zoom 2', 5, 16],
            ['zoom 2', 6, 16],
            ['zoom 2', 7, 16],
            ['zoom 2', 8, 16],
            ['zoom 2', 9, 16],
            ['zoom 2', 10, 16],
            ['zoom 2', 11, 16],
            ['zoom 2', 12, 16],
            ['zoom 2', 13, 16],
            ['zoom 2', 14, 16],
            ['zoom 2', 15, 16],
            ['zoom 2', 16, 16],
        ], $logs);

        $this->assertCount(64, glob($path . '/tiles/*.png'));
        $this->assertEqualsCanonicalizing([
            $path . '/maps/4_4.png',
            $path . '/maps/4_5.png',
            $path . '/maps/5_4.png',
            $path . '/maps/5_5.png',
        ], glob($path . '/maps/*.png'));
        $this->assertEmpty(glob($path . '/zoom/0/*.png'));
        $this->assertEmpty(glob($path . '/zoom/1/*.png'));
        $this->assertCount(16, glob($path . '/zoom/2/*.png'));
    }

    #[Test]
    public function properties()
    {
        $renderer = new TileRenderer(
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf')),
            ),
            function (TileMapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../../_files/' . $mapId . '.key')
                );
            },
            new Bounds(
                min(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[0], array_keys(self::MAPS))),
                min(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
                max(array_map(fn ($value) => (int) explode(',', $value)[1], array_keys(self::MAPS))),
            ),
        );

        $this->assertSame(3, $renderer->maxZoom);
        $this->assertEquals(new Bounds(4, 5, 4, 5), $renderer->bounds);
    }
}
