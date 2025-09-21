<?php

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\Layer\BackgroundLayerRenderer;
use Arakne\MapParser\Renderer\Layer\LayerObjectRenderer;
use Arakne\MapParser\Renderer\Layer\LayerRendererInterface;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use GdImage;
use Override;

use function assert;
use function imagecreatetruecolor;
use function imagescale;
use function imagesx;

/**
 * Base dofus map renderer
 *
 * @psalm-api
 */
final readonly class MapRenderer implements MapRendererInterface
{
    /**
     * The output image width in pixels
     */
    public const int DISPLAY_WIDTH = 742;

    /**
     * The output image height in pixels
     */
    public const int DISPLAY_HEIGHT = 432;

    public const int CELL_WIDTH = 53;
    public const int CELL_HEIGHT = 27;
    public const float CELL_HALF_WIDTH = 2.650000E+001;
    public const float CELL_HALF_HEIGHT = 1.350000E+001;

    public const int LEVEL_HEIGHT = 20;

    /**
     * The default map width in cells
     */
    public const int DEFAULT_WIDTH = 15;

    /**
     * The default map height in cells
     */
    public const int DEFAULT_HEIGHT = 17;

    public function __construct(
        private SpriteRepositoryInterface $grounds,
        private SpriteRepositoryInterface $objects,
    ) {}

    #[Override]
    public function render(Map $map): GdImage
    {
        $hasCustomSize = $map->width !== self::DEFAULT_WIDTH || $map->height !== self::DEFAULT_HEIGHT;

        if (!$hasCustomSize) {
            $img = imagecreatetruecolor(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);
        } else {
            $img = imagecreatetruecolor(
                ($map->width - 1) * self::CELL_WIDTH,
                ($map->height - 1) * self::CELL_HEIGHT,
            );
        }

        $shapes = CellShape::fromMap($map);

        // @todo inject layers
        /** @var LayerRendererInterface[] $layers */
        $layers = [
            new BackgroundLayerRenderer($this->grounds),
            new LayerObjectRenderer($this->grounds, static fn(CellShape $cell) => $cell->data->ground),
            new LayerObjectRenderer($this->objects, static fn(CellShape $cell) => $cell->data->layer1),
            new LayerObjectRenderer($this->objects, static fn(CellShape $cell) => $cell->data->layer2),
        ];

        foreach ($layers as $layer) {
            $layer->render($map, $shapes, $img);
        }

        if ($hasCustomSize) {
            $img = $this->rescaleMap($map, $img);
        }

        return $img;
    }

    /**
     * Resize the map to fit in the display area
     *
     * @param Map $map
     * @param GdImage $img
     *
     * @return GdImage
     *
     * @see https://github.com/Emudofus/Dofus/blob/1.29/ank/battlefield/mc/Container.as#L154
     */
    private function rescaleMap(Map $map, GdImage $img): GdImage
    {
        $actualWidth = imagesx($img);
        $actualHeight = imagesy($img);

        // Scaling is only applied if both dimensions are greater than the default size
        // Otherwise, the map is displayed at its original size and simply cropped/centered
        if ($map->height > self::DEFAULT_HEIGHT && $map->width > self::DEFAULT_WIDTH) {
            $scale = $map->height > $map->width
                ? self::DISPLAY_WIDTH / (($map->width - 1) * self::CELL_WIDTH)
                : self::DISPLAY_HEIGHT / (($map->height - 1) * self::CELL_HEIGHT)
            ;

            $actualWidth = (int) (($map->width - 1) * self::CELL_WIDTH * $scale);
            $actualHeight = (int) (($map->height - 1) * self::CELL_HEIGHT * $scale);

            $img = imagescale($img, $actualWidth, $actualHeight);
            assert($img !== false);
        }

        // Map has the correct size, no need to crop
        if ($actualWidth === self::DISPLAY_WIDTH && $actualHeight === self::DISPLAY_HEIGHT) {
            return $img;
        }

        $result = imagecreatetruecolor(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);
        assert($result !== false);

        $offsetX = (self::DISPLAY_WIDTH - $actualWidth) / 2;
        $offsetY = (self::DISPLAY_HEIGHT - $actualHeight) / 2;

        imagecopy($result, $img, (int) $offsetX, (int) $offsetY, 0, 0, $actualWidth, $actualHeight);

        return $result;
    }
}
