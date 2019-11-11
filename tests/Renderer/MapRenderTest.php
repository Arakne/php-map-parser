<?php


namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Test\AssertImageTrait;
use PHPUnit\Framework\TestCase;
use Swf\Cli\Jar;
use Swf\SwfLoader;

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

    protected function setUp()
    {
        $swfLoader = new SwfLoader(new Jar(__DIR__.'/../../../../.local/app/ffdec_11.2.0_nightly1722/ffdec.jar'));

        $this->renderer = new MapRenderer(
            $swfLoader->bulk(glob(__DIR__.'/../_files/clips/gfx/g*.swf')),
            $swfLoader->bulk(glob(__DIR__.'/../_files/clips/gfx/o*.swf'))
        );
    }

    /**
     *
     */
    public function test_render()
    {
        $map = new Map(0, 15, 17, (new CellDataParser())->parse(file_get_contents(__DIR__.'/../_files/10340.data')));
        $img = $this->renderer->render($map);

        $img->save(__DIR__.'/_files/render.png');

        $this->assertEquals(MapRenderer::DISPLAY_HEIGHT, $img->height());
        $this->assertEquals(MapRenderer::DISPLAY_WIDTH, $img->width());

        $img->destroy();

        $this->assertImages(__DIR__.'/_files/10340.png', __DIR__.'/_files/render.png');
        unlink(__DIR__.'/_files/render.png');
    }
}
