<?php


use Arakne\MapParser\DofusMapParser;
use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Sprite\Cache\SqliteSpriteCache;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Tile\Cache\SqliteCache;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DofusMapParserTest extends TestCase
{
    use AssertImageTrait;

    public const array MAPS = [
        '4,4' => 10332,
        '5,4' => 10319,
        '5,5' => 10334,
        '4,5' => 10333,
        '3,6' => 10340,
    ];

    private static string $tileCacheFile;
    private static string $spriteCacheFile;

    private DofusMapParser $parser;

    public static function setUpBeforeClass(): void
    {
        self::$tileCacheFile = tempnam('/tmp', 'dofusmaptest');
        self::$spriteCacheFile = tempnam('/tmp', 'dofusmaptest');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$tileCacheFile);
        @unlink(self::$spriteCacheFile);
    }

    protected function setUp(): void
    {
        $this->parser = new DofusMapParser(
            dofusPath: __DIR__ . '/_files',
            mapsPath: __DIR__ . '/_files',
            mapByCoordinates: fn (MapCoordinates $coords): ?int => self::MAPS[$coords->x . ',' . $coords->y] ?? null,
            tileCache: new SqliteCache(self::$tileCacheFile),
            spriteCache: new SqliteSpriteCache(self::$spriteCacheFile),
            attachmentsProviders: [
                function (MapStructure $map) {
                    $attachments = [];

                    if ($map->encrypted) {
                        $attachments[] = MapKey::fromFile(__DIR__ . '/_files/' . $map->id . '.key');
                    }

                    if ($pos = array_search($map->id, self::MAPS, true)) {
                        [$x, $y] = explode(',', $pos);
                        $attachments[] = new MapCoordinates((int)$x, (int)$y);
                    }

                    return $attachments;
                },
            ]
        );
    }

    #[Test]
    public function renderWithMapId()
    {
        $img = $this->parser->render(10340);
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render.png');

        $this->assertImages(__DIR__ . '/Renderer/_files/10340.png', $path);
        unlink($path);
    }

    #[Test]
    public function renderWithSwfFile()
    {
        $img = $this->parser->render(new SwfFile(__DIR__ . '/_files/10340_0706131721X.swf'));
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render.png');

        $this->assertImages(__DIR__ . '/Renderer/_files/10340.png', $path);
        unlink($path);
    }

    #[Test]
    public function renderWithMapStructure()
    {
        $img = $this->parser->render(
            new MapStructure(
                id: 10340,
                width: 15,
                height: 17,
                data: file_get_contents(__DIR__ . '/_files/10340.data'),
            )
        );
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render.png');

        $this->assertImages(__DIR__ . '/Renderer/_files/10340.png', $path);
        unlink($path);
    }

    #[Test]
    public function renderInvalidMap()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->render(404);
    }

    #[Test]
    public function loadWithMapId()
    {
        $map = $this->parser->load(10340);

        $this->assertSame(10340, $map->id);
        $this->assertCount(479, $map->cells);
        $this->assertSame(15, $map->width);
        $this->assertSame(17, $map->height);
        $this->assertTrue($map->get(MapStructure::class)->encrypted);
        $this->assertSame(3, $map->x);
        $this->assertSame(6, $map->y);
    }

    #[Test]
    public function loadWithSwfFile()
    {
        $map = $this->parser->load(new SwfFile(__DIR__ . '/_files/10340_0706131721X.swf'));

        $this->assertSame(10340, $map->id);
        $this->assertCount(479, $map->cells);
        $this->assertSame(15, $map->width);
        $this->assertSame(17, $map->height);
        $this->assertTrue($map->get(MapStructure::class)->encrypted);
        $this->assertSame(3, $map->x);
        $this->assertSame(6, $map->y);
    }

    #[Test]
    public function loadWithMapStructure()
    {
        $map = $this->parser->load(
            new MapStructure(
                id: 10340,
                width: 15,
                height: 17,
                data: file_get_contents(__DIR__ . '/_files/10340.data'),
            )
        );

        $this->assertSame(10340, $map->id);
        $this->assertCount(479, $map->cells);
        $this->assertSame(15, $map->width);
        $this->assertSame(17, $map->height);
        $this->assertFalse($map->get(MapStructure::class)->encrypted);
        $this->assertSame(3, $map->x);
        $this->assertSame(6, $map->y);
    }

    #[Test]
    public function loadInvalidMap()
    {
        $this->assertNull($this->parser->load(404));
    }

    #[Test]
    public function incarnamWorldMapFunctional()
    {
        $worldMap = $this->parser->incarnamWorldMap(-4);

        $img = $worldMap->render(0, 0, 0);
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render_incarnam_0.png');
        $this->assertImages(__DIR__ . '/WorldMap/Fixtures/combined/expected_0_0_0.png', $path);
         @unlink($path);

        $img = $worldMap->render(6, 3, 4);
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render_incarnam_4.png');
        $this->assertImages(__DIR__ . '/WorldMap/Fixtures/combined/expected_6_3_4.png', $path);
         @unlink($path);
    }

    #[Test]
    public function incarnamWorldMapWithoutGameMapFunctional()
    {
        $parser = new DofusMapParser(dofusPath: __DIR__ . '/_files');
        $worldMap = $parser->incarnamWorldMap(4);

        $img = $worldMap->render(0, 0, 0);
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render_incarnam_0.png');
        $this->assertImages(__DIR__ . '/WorldMap/Fixtures/combined/expected_0_0_0.png', $path);
         @unlink($path);

        $img = $worldMap->render(6, 3, 4);
        imagesavealpha($img, true);
        imagepng($img, $path = __DIR__ . '/_files/render_incarnam_4.png');
        $this->assertImages(__DIR__ . '/WorldMap/Fixtures/combined/expected_6_3_4_no_gm.png', $path);
         @unlink($path);
    }

    #[Test]
    public function amaknaWorldMap()
    {
        $worldMap = $this->parser->amaknaWorldMap(5);

        $this->assertSame(9, $worldMap->maxZoom);
        $this->assertEquals(new Bounds(
            xMin: -90,
            xMax: 44,
            yMin: -120,
            yMax: 59,
        ), $worldMap->bounds);
    }
}
