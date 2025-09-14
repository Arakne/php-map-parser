<?php

namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\LayerObjectInterface;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use Closure;
use GdImage;

use function imagecopy;

/**
 * Render a layer object, by extracting the sprite from swf
 */
final class LayerObjectRenderer implements LayerRendererInterface
{
    public function __construct(
        private readonly SpriteRepositoryInterface $sprites,

        /**
         * The function to get the object from the cell
         *
         * @var Closure(CellShape): LayerObjectInterface
         */
        private readonly Closure $getter,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function render(Map $map, array $cells, GdImage $out): void
    {
        foreach ($cells as $cell) {
            $this->renderCell($cell, $out);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderCell(CellShape $cell, GdImage $out): void
    {
        if (!$object = $this->getObject($cell)) {
            return;
        }

        $sprite = $this->sprites->get($object->number());

        if (!$sprite->valid) {
            return;
        }

        if ($object->flip()) {
            $sprite = $sprite->flip();
        }

        $img = $sprite->gd();
        $y = $cell->y() + $sprite->offsetY;
        $x = $cell->x() + $sprite->offsetX;

        imagecopy($out, $img, $x, $y, 0, 0, $sprite->width, $sprite->height);
    }

    /**
     * Get the layer object from the cell
     *
     * @param CellShape $cell
     *
     * @return LayerObjectInterface|null The layer object, or null if not active
     */
    private function getObject(CellShape $cell): ?LayerObjectInterface
    {
        $object = ($this->getter)($cell);

        if (!$object->active()) {
            return null;
        }

        return $object;
    }
}
