<?php

namespace Arakne\MapParser\Renderer\Layer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Sprite\SpriteRepositoryInterface;
use GdImage;
use Override;

/**
 * Renders the background layer of the map.
 */
final readonly class BackgroundLayerRenderer implements LayerRendererInterface
{
    public function __construct(
        /**
         * The ground sprite repository.
         */
        private SpriteRepositoryInterface $repository,
    ) {}

    #[Override]
    public function render(Map $map, array $cells, GdImage $out): void
    {
        if ($map->background === 0) {
            return;
        }

        $bg = $this->repository->get($map->background);

        if ($bg->valid) {
            imagecopy($out, $bg->gd(), (int) $bg->offsetX, (int) $bg->offsetY, 0, 0, (int) $bg->width, (int) $bg->height);
        }
    }
}
