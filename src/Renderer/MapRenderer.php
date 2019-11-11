<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\Layer\LayerObjectRenderer;
use Arakne\MapParser\Renderer\Layer\LayerRendererInterface;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Swf\Processor\BulkLoader;

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

    /**
     * @var BulkLoader
     */
    private $groundLoader;

    /**
     * @var BulkLoader
     */
    private $objectLoader;

    /**
     * @var ImageManager
     */
    private $imageManager;

    public function __construct(BulkLoader $groundLoader, BulkLoader $objectLoader, ImageManager $imageManager = null)
    {
        $this->groundLoader = $groundLoader;
        $this->objectLoader = $objectLoader;
        $this->imageManager = $imageManager ?: new ImageManager();
    }

    public function render(Map $map): Image
    {
        $img = $this->imageManager->canvas(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);

        $shapes = CellShape::fromMap($map);

        /** @var LayerRendererInterface[] $layers */
        $layers = [
            new LayerObjectRenderer($this->imageManager, $this->groundLoader, function (CellShape $cell) { return $cell->data()->ground(); }),
            new LayerObjectRenderer($this->imageManager, $this->objectLoader, function (CellShape $cell) { return $cell->data()->layer1(); }),
            new LayerObjectRenderer($this->imageManager, $this->objectLoader, function (CellShape $cell) { return $cell->data()->layer2(); }),
        ];

        foreach ($layers as $layer) {
            $layer->prepare($map, $shapes);
        }

        foreach ($layers as $layer) {
            $layer->render($map, $shapes, $img);
        }

        return $img;
    }
}
