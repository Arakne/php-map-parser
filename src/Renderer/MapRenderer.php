<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\Layer\BackgroundLayerRenderer;
use Arakne\MapParser\Renderer\Layer\LayerObjectRenderer;
use Arakne\MapParser\Renderer\Layer\LayerRendererInterface;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use GdImage;

use function imagecreatetruecolor;

/**
 * Render a map
 */
class MapRenderer
{
    const DISPLAY_WIDTH = 742;
    const DISPLAY_HEIGHT = 432;

    const CELL_WIDTH = 53;
    const CELL_HEIGHT = 27;
    const CELL_HALF_WIDTH = 2.650000E+001;
    const CELL_HALF_HEIGHT = 1.350000E+001;

    const LEVEL_HEIGHT = 20;

    public function __construct(
        private readonly SpriteRepositoryInterface $grounds,
        private readonly SpriteRepositoryInterface $objects,
    ) {}

    public function render(Map $map): GdImage
    {
        $img = imagecreatetruecolor(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);
        $shapes = CellShape::fromMap($map);

        /** @var LayerRendererInterface[] $layers */
        $layers = [
            new BackgroundLayerRenderer($this->grounds),
            new LayerObjectRenderer($this->grounds, static fn (CellShape $cell) => $cell->data()->ground()),
            new LayerObjectRenderer($this->objects, static fn (CellShape $cell) => $cell->data()->layer1()),
            new LayerObjectRenderer($this->objects, static fn (CellShape $cell) => $cell->data()->layer2()),
        ];

        foreach ($layers as $layer) {
            $layer->render($map, $shapes, $img);
        }

        return $img;
    }
}
