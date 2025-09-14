<?php


namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\MapParser\Util\XorCipher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function imagepng;
use function imagesx;

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

        imagepng($img, __DIR__.'/_files/render.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/10340.png', __DIR__.'/_files/render.png');
        unlink(__DIR__.'/_files/render.png');
    }

    #[Test]
    public function render_with_background()
    {
        $map = new Map(0, 15, 17, 438, (new CellDataParser())->parse(
            XorCipher::fromHexKey(file_get_contents(__DIR__.'/../_files/10302.key'))->decrypt(file_get_contents(__DIR__.'/../_files/10302.data'))
        ));
        $img = $this->renderer->render($map);

        imagepng($img, __DIR__.'/_files/render.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, imagesy($img));
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, imagesx($img));

        $this->assertImages(__DIR__.'/_files/10302.png', __DIR__.'/_files/render.png');
        unlink(__DIR__.'/_files/render.png');
    }
}
