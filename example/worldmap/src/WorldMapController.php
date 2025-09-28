<?php

namespace Example\Worldmap;

use Arakne\MapParser\Tile\TileRendererInterface;
use Closure;

final readonly class WorldMapController
{
    public function __construct(
        private TileRendererInterface $tileRenderer,
        private string $name,
    ) {}

    public function __invoke(array $get, Closure $sender): void
    {
        ob_start();
        include __DIR__.'/worldmap.html.php';
        $sender(ob_get_clean(), ['Content-Type' => 'text/html']);
    }
}
