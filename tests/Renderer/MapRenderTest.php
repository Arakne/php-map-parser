<?php


namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Util\XorCipher;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function imagepng;
use function imagesx;
use function imagesy;
use function unlink;

/**
 * Class MapRenderTest
 */
class MapRenderTest extends TestCase
{
    use AssertImageTrait;

    /**
     * @var MapRenderer
     */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = new MapRenderer(
            new SwfSpriteRepository(glob(__DIR__.'/../_files/clips/gfx/g*.swf')),
            new SwfSpriteRepository(glob(__DIR__.'/../_files/clips/gfx/o*.swf')),
        );
    }

    #[Test]
    public function render()
    {
        $map = new Map(0, 15, 17, 0, (new CellDataParser())->parse(file_get_contents(__DIR__.'/../_files/10340.data')));
        $img = $this->renderer->render($map);

        imagepng($img, __DIR__ . '/_files/37.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/10340.png', __DIR__ . '/_files/37.png');
        unlink(__DIR__ . '/_files/37.png');
    }

    #[Test]
    public function render_with_background()
    {
        $map = new Map(0, 15, 17, 438, (new CellDataParser())->parse(
            XorCipher::fromHexKey(file_get_contents(__DIR__.'/../_files/10302.key'))->decrypt(file_get_contents(__DIR__.'/../_files/10302.data'))
        ));
        $img = $this->renderer->render($map);

        imagepng($img, __DIR__ . '/_files/37.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/10302.png', __DIR__ . '/_files/37.png');
        unlink(__DIR__ . '/_files/37.png');
    }

    #[Test]
    public function renderBiggerDimensions()
    {
        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(new SwfFile(__DIR__ .'/../_files/4208_0706131721X.swf')),
            MapKey::fromFile(__DIR__ .'/../_files/4208.key'),
        );

        $img = $this->renderer->render($map);

        imagepng($img, __DIR__ . '/_files/37.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/4208.png', __DIR__ . '/_files/37.png');
        unlink(__DIR__ . '/_files/37.png');
    }

    #[Test]
    public function renderSmallerDimensions()
    {
        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(new SwfFile(__DIR__ .'/../_files/703_0706131721X.swf')),
            MapKey::fromFile(__DIR__ .'/../_files/703.key'),
        );

        $img = $this->renderer->render($map);

        imagepng($img, __DIR__ . '/_files/37.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/703.png', __DIR__ . '/_files/37.png', 0.002);
        unlink(__DIR__ . '/_files/37.png');
    }

    #[Test]
    public function renderWithSpriteRotation()
    {
        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(new SwfFile(__DIR__ .'/../_files/37_0904061612X.swf')),
            MapKey::fromFile(__DIR__ .'/../_files/37.key'),
        );

        $img = $this->renderer->render($map);

        imagepng($img, __DIR__ . '/_files/37.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/37.png', __DIR__ . '/_files/37.png', 0.002);
        unlink(__DIR__ . '/_files/37.png');
    }
}
