<?php

namespace Example\Worldmap;

use Arakne\MapParser\DofusMapParser;
use Closure;

final readonly class RenderMapController
{
    public function __construct(
        private DofusMapParser $parser,
    ) {}

    public function __invoke(array $get, Closure $sender): void
    {
        $mapId = (int) $get['id'] ?? 0;

        try {
            $img = $this->parser->render($mapId);
        } catch (\InvalidArgumentException) {
            $sender('Map not found', ['Content-Type' => 'text/plain']);
            return;
        }

        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();

        $sender($imageData, ['Content-Type' => 'image/png']);
    }
}
