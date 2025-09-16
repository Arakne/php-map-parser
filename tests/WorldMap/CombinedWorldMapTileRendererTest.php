<?php

namespace WorldMap;

use _PHPStan_1c270d899\Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Tile\Cache\FilesystemTileCache;
use Arakne\MapParser\Tile\MapCoordinates;
use Arakne\MapParser\WorldMap\CombinedWorldMapTileRenderer;
use Arakne\MapParser\WorldMap\SwfWorldMap;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use SplFileInfo;

use function bin2hex;
use function file_get_contents;
use function glob;
use function imagepng;
use function mkdir;
use function random_bytes;
use function unlink;

class CombinedWorldMapTileRendererTest extends TestCase
{
    use AssertImageTrait;

    public const array MAPS = [
        '4,4' => 10332,
        '5,4' => 10319,
        '5,5' => 10334,
        '4,5' => 10333,
    ];
    private static string $cacheDir;

    private CombinedWorldMapTileRenderer $renderer;

    public static function setUpBeforeClass(): void
    {
        self::$cacheDir = '/tmp/' . bin2hex(random_bytes(4));
        mkdir(self::$cacheDir, 0777, true);
    }

    public static function tearDownAfterClass(): void
    {
        /** @var SplFileInfo $path
         */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$cacheDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($path->isDir()) {
                rmdir($path->getPathname());
            } else {
                unlink($path->getPathname());
            }
        }
    }

    protected function setUp(): void
    {
        $this->renderer = new CombinedWorldMapTileRenderer(
            new SwfWorldMap(new SwfFile(__DIR__ . '/Fixtures/3.swf')),
            new MapRenderer(
                new SwfSpriteRepository(glob(__DIR__ . '/../_files/clips/gfx/g*.swf')),
                new SwfSpriteRepository(glob(__DIR__ . '/../_files/clips/gfx/o*.swf')),
            ),
            function (MapCoordinates $coords) {
                if (!($mapId = self::MAPS["{$coords->x},{$coords->y}"] ?? null)) {
                    return null;
                }

                return MapStructure::fromSwfFile(
                    new SwfFile(glob(__DIR__ . '/../_files/' . $mapId . '*.swf')[0]),
                    file_get_contents(__DIR__ . '/../_files/' . $mapId . '.key')
                );
            },
            4,
            cache: new FilesystemTileCache(self::$cacheDir),
        );
    }

    #[
        Test,
        TestWith([0, 0, 0]),
        TestWith([0, 0, 1]),
        TestWith([1, 0, 2]),
        TestWith([3, 1, 3]),
        TestWith([6, 3, 4]),
        TestWith([13, 7, 5]),
        TestWith([26, 15, 6]),
    ]
    public function render(int $x, int $y, int $zoom): void
    {
        $img = $this->renderer->render($x, $y, $zoom);
        $actual = __DIR__ . '/Fixtures/combined/actual_' . $x . '_' . $y . '_' . $zoom . '.png';
        $expected = __DIR__ . '/Fixtures/combined/expected_' . $x . '_' . $y . '_' . $zoom . '.png';

        imagepng($img, $actual);

        $this->assertImages($expected, $actual);

        unlink($actual);
    }

    #[Test]
    public function renderOriginalSize(): void
    {
        $img = $this->renderer->renderOriginalSize(111, 62);
        $actual = __DIR__ . '/Fixtures/combined/actual_original_size.png';
        $expected = __DIR__ . '/Fixtures/combined/expected_original_size.png';

        imagepng($img, $actual);

        $this->assertImages($expected, $actual);
        unlink($actual);
    }
}
