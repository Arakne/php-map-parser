<?php

namespace Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\Layer\BackgroundLayerRenderer;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function glob;
use function imagepng;
use function unlink;

class BackgroundLayerRendererTest extends TestCase
{
    use AssertImageTrait;

     #[Test]
    public function render()
    {
        $renderer = new BackgroundLayerRenderer(new SwfSpriteRepository(glob(__DIR__.'/../../_files/clips/gfx/g*.swf')));
        $map = new Map(0, 15, 17, 438, []);

        $img = imagecreatetruecolor(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        $renderer->render($map, [], $img);

        imagepng($img, $path = __DIR__.'/../_files/bg.png');

        $this->assertImages(__DIR__.'/../_files/10302-bg.png', $path);
        unlink($path);
    }

    #[Test]
    public function render_no_background()
    {
        $renderer = new BackgroundLayerRenderer(new SwfSpriteRepository(glob(__DIR__.'/../../_files/clips/gfx/g*.swf')));
        $map = new Map(0, 15, 17, 0, []);

        $img = imagecreatetruecolor(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        $renderer->render($map, [], $img);

        imagepng($img, $path = __DIR__.'/../_files/bg.png');

        $this->assertImages(__DIR__.'/../_files/empty.png', $path);
        unlink($path);
    }

    #[Test]
    public function render_invalid_background()
    {
        $renderer = new BackgroundLayerRenderer(new SwfSpriteRepository(glob(__DIR__.'/../../_files/clips/gfx/g*.swf')));
        $map = new Map(0, 15, 17, 404, []);

        $img = imagecreatetruecolor(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        $renderer->render($map, [], $img);

        imagepng($img, $path = __DIR__.'/../_files/bg.png');

        $this->assertImages(__DIR__.'/../_files/empty.png', $path);
        unlink($path);
    }
}
