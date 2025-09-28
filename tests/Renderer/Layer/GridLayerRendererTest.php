<?php

namespace Renderer\Layer;

use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\Layer\GridLayerRenderer;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function imagepng;
use function unlink;

class GridLayerRendererTest extends TestCase
{
    use AssertImageTrait;

    #[Test]
    public function renderFlat()
    {
        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../../_files/10340_0706131721X.swf')),
            MapKey::fromFile(__DIR__ . '/../../_files/10340.key')
        );

        $renderer = new GridLayerRenderer();
        $out = imagecreatetruecolor(742, 432);
        $cells = CellShape::fromMap($map);

        $renderer->render($map, $cells, $out);
        imagepng($out, $path = __DIR__ . '/../_files/expected.png');

        $this->assertImages(__DIR__ . '/../_files/10340-grid.png', $path);
        @unlink($path);
    }

    #[Test]
    public function renderWithRelief()
    {
        $map = new MapLoader()->load(
            MapStructure::fromSwfFile(new SwfFile(__DIR__ . '/../../_files/745_0706131721X.swf')),
            MapKey::fromFile(__DIR__ . '/../../_files/745.key')
        );

        $renderer = new GridLayerRenderer();
        $out = imagecreatetruecolor(742, 432);
        $cells = CellShape::fromMap($map);

        $renderer->render($map, $cells, $out);
        imagepng($out, $path = __DIR__ . '/../_files/expected.png');

        $this->assertImages(__DIR__ . '/../_files/745-grid.png', $path);
        @unlink($path);
    }
}
