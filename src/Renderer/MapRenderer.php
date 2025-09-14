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
 *
 * @todo interface
 */
final readonly class MapRenderer
{
    const int DISPLAY_WIDTH = 742;
    const int DISPLAY_HEIGHT = 432;

    const int CELL_WIDTH = 53;
    const int CELL_HEIGHT = 27;
    const float CELL_HALF_WIDTH = 2.650000E+001;
    const float CELL_HALF_HEIGHT = 1.350000E+001;

    const int LEVEL_HEIGHT = 20;

    public function __construct(
        private SpriteRepositoryInterface $grounds,
        private SpriteRepositoryInterface $objects,
    ) {}

    /**
     * Render the given map as a GD image
     *
     * @param Map $map
     * @return GdImage
     */
    public function render(Map $map): GdImage
    {
        $img = imagecreatetruecolor(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);
        $shapes = CellShape::fromMap($map);

        // @todo inject layers
        /** @var LayerRendererInterface[] $layers */
        $layers = [
            new BackgroundLayerRenderer($this->grounds),
            new LayerObjectRenderer($this->grounds, static fn (CellShape $cell) => $cell->data->ground),
            new LayerObjectRenderer($this->objects, static fn (CellShape $cell) => $cell->data->layer1),
            new LayerObjectRenderer($this->objects, static fn (CellShape $cell) => $cell->data->layer2),
        ];

        foreach ($layers as $layer) {
            $layer->render($map, $shapes, $img);
        }

        return $img;
    }
}
