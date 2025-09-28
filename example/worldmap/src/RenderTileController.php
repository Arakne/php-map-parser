<?php

namespace Example\Worldmap;

use Arakne\MapParser\Tile\TileRendererInterface;
use Closure;

final readonly class RenderTileController
{
    public function __construct(
        private TileRendererInterface $tileRenderer,
    ) {}

    public function __invoke(array $get, Closure $sender): void
    {
        $x = (int) $get['x'] ?? 0;
        $y = (int) $get['y'] ?? 0;
        $zoom = (int) $get['z'] ?? 0;

        $maxCoordinate = (1 << $zoom) - 1;

        if ($x < 0 || $x > $maxCoordinate || $y < 0 || $y > $maxCoordinate) {
            $sender('Invalid coordinates', ['Content-Type' => 'text/plain']);
            return;
        }

        $img = $this->tileRenderer->render($x, $y, $zoom);

        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();

        $sender($imageData, ['Content-Type' => 'image/png']);
    }
}
