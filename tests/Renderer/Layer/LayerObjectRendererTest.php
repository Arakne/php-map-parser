<?php


namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Swf\Cli\Jar;
use Swf\Processor\BulkLoader;
use Swf\SwfLoader;

use function imagecreatetruecolor;
use function imagepng;

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
     * @var SpriteRepositoryInterface
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

    protected function setUp(): void
    {
        $this->renderer = new LayerObjectRenderer(
            $this->loader = new SwfSpriteRepository(glob(__DIR__.'/../../_files/clips/gfx/g*.swf')),
            function (CellShape $cell) { return $cell->data()->ground(); }
        );

        $this->map = new Map(0, 15, 17, 0, (new CellDataParser())->parse(file_get_contents(__DIR__.'/../../_files/10340.data')));
        $this->cells = CellShape::fromMap($this->map);
    }

    /**
     *
     */
    public function test_render()
    {
        $img = imagecreatetruecolor(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);

        $this->renderer->render($this->map, $this->cells, $img);

        imagepng($img, $path = __DIR__.'/../_files/layer.png');

        $this->assertImages(__DIR__.'/../_files/10340-ground.png', $path, 0.011);
        unlink($path);
    }
}
