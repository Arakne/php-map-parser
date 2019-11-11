<?php


namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Test\AssertImageTrait;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Swf\Cli\Jar;
use Swf\Processor\BulkLoader;
use Swf\SwfLoader;

/**
 * Class LayerObjectRendererTest
 */
class LayerObjectRendererTest extends TestCase
{
    use AssertImageTrait;

    /**
     * @var LayerObjectRenderer
     */
    private $renderer;

    /**
     * @var BulkLoader
     */
    private $loader;

    /**
     * @var Map
     */
    private $map;

    /**
     * @var CellShape[]
     */
    private $cells;

    protected function setUp()
    {
        $swfLoader = new SwfLoader(new Jar(__DIR__.'/../../../../../.local/app/ffdec_11.2.0_nightly1722/ffdec.jar'));

        $this->renderer = new LayerObjectRenderer(
            new ImageManager(),
            $this->loader = $swfLoader->bulk(glob(__DIR__.'/../../_files/clips/gfx/g*.swf')),
            function (CellShape $cell) { return $cell->data()->ground(); }
        );

        $this->map = new Map(0, 15, 17, (new CellDataParser())->parse(file_get_contents(__DIR__.'/../../_files/10340.data')));
        $this->cells = CellShape::fromMap($this->map);
    }

    /**
     *
     */
    public function test_prepare()
    {
        $this->renderer->prepare($this->map, $this->cells);

        $this->assertNotNull($this->loader->get(428));
        $this->assertNotNull($this->loader->get(429));
        $this->assertNotNull($this->loader->get(431));
    }

    /**
     *
     */
    public function test_render()
    {
        $this->renderer->prepare($this->map, $this->cells);

        $img = (new ImageManager())->canvas(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);

        $this->renderer->render($this->map, $this->cells, $img);

        $img->save($path = __DIR__.'/../_files/layer.png');
        $img->destroy();

        $this->assertImages(__DIR__.'/../_files/10340-ground.png', $path);
        unlink($path);
    }
}
