<?php

namespace Example\Worldmap;

use Arakne\MapParser\DofusMapParser;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\MapRenderer;
use Closure;

final readonly class ShowMapController
{
    public function __construct(
        private DofusMapParser $parser,
        private MapRepository $repository,
    ) {}

    public function __invoke(array $get, Closure $sender): void
    {
        $mapId = (int) $get['id'] ?? 0;
        $map = $this->parser->load($mapId);

        if (!$map) {
            $sender('Map not found', ['Content-Type' => 'text/plain']);
            return;
        }

        $triggers = array_map(function ($trigger) use ($map) {
            $cell = CellShape::fromCellId($map, (int)$trigger['CELL_ID'])->toDisplayPosition($map);

            return [
                'x' => $cell->x,
                'y' => $cell->y,
                'target' => (int) explode(',', $trigger['ARGUMENTS'])[0],
            ];
        }, $this->repository->loadTriggers([$mapId])[$mapId] ?? []);

        $width = MapRenderer::DISPLAY_WIDTH;
        $height = MapRenderer::DISPLAY_HEIGHT;

        ob_start();
        require __DIR__ . '/map.html.php';
        $content = ob_get_clean();

        $sender($content);
    }
}
