<?php

namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Parser\LayerObjectInterface;
use Arakne\MapParser\Renderer\CellShape;
use Bdf\Collection\Stream\Streams;
use Bdf\Collection\Util\Functor\Transformer\Getter;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Swf\Processor\BulkLoader;

/**
 * Render a layer object, by extracting the sprite from swf
 */
final class LayerObjectRenderer implements LayerRendererInterface
{
    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var BulkLoader
     */
    private $loader;

    /**
     * @var callable
     */
    private $getter;


    /**
     * LayerObjectRenderer constructor.
     *
     * @param ImageManager $imageManager The image manager
     * @param BulkLoader $loader Loader for sprites
     * @param callable $getter Get the cell object layer. Prototype : function (CellShape $cell): LayerObjectInterface
     */
    public function __construct(ImageManager $imageManager, BulkLoader $loader, callable $getter)
    {
        $this->imageManager = $imageManager;
        $this->loader = $loader;
        $this->getter = $getter;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(Map $map, array $cells): void
    {
        Streams::wrap($cells)
            ->map($this->getter)
            ->filter(new Getter('active'))
            ->map(new Getter('number'))
            ->forEach([$this->loader, 'add'])
        ;

        $this->loader->load();
    }

    /**
     * {@inheritdoc}
     */
    public function render(Map $map, array $cells, Image $out): void
    {
        foreach ($cells as $cell) {
            $this->renderCell($cell, $out);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderCell(CellShape $cell, Image $out): void
    {
        if (!$object = $this->getObject($cell)) {
            return;
        }

        $sprite = $this->loader->get($object->number());

        // @todo exception ?
        if (!$sprite || !$sprite->bounds() || $sprite->bounds()->width() == 0) {
            //throw new \RuntimeException('Invalid bounds '.$sprite->frame());
            return;
        }

        $img = $this->imageManager->make($sprite->frame());

        $y = $cell->y() + $sprite->bounds()->Yoffset();

        if ($object->flip()) {
            $img->flip();

            $x = $cell->x() - $sprite->bounds()->Xoffset() - $sprite->bounds()->width();
        } else {
            $x = $cell->x() + $sprite->bounds()->Xoffset();
        }

        $out->insert($img, 'top-left', $x, $y);
        $img->destroy();
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
