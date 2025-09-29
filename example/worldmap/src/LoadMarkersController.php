<?php

namespace Example\Worldmap;

use Arakne\MapParser\DofusMapParser;
use Arakne\MapParser\Tile\Coordinate\LatLongBounds;
use Arakne\MapParser\Tile\TileRendererInterface;
use Closure;

use function array_keys;
use function json_encode;

final readonly class LoadMarkersController
{
    public function __construct(
        private DofusMapParser $parser,
        private TileRendererInterface $tileRenderer,
        private MapRepository $repository,
        private int $superAreaId,
    ) {}

    public function __invoke(array $get, Closure $sender): void
    {
        $bbox = LatLongBounds::fromString($get['bbox'] ?? '');

        if (!$bbox) {
            $sender('Invalid bbox', ['Content-Type' => 'text/plain']);
            return;
        }

        $bounds = $this->tileRenderer->coordinate->toMapBounds($bbox);

        $width = $bounds->xMax - $bounds->xMin + 1;
        $height = $bounds->yMax - $bounds->yMin + 1;
        $mapsCount = $width * $height;

        if ($mapsCount > 100) {
            $sender('Bounding box too large', ['Content-Type' => 'text/plain']);
            return;
        }

        $maps = [];

        foreach ($this->repository->findMapsInBounds($bounds, $this->superAreaId) as $structure) {
            $maps[$structure->id] = $this->parser->load($structure);
        }

        $triggers = $this->repository->loadTriggers(array_keys($maps));
        $points = [];

        foreach ($triggers as $mapId => $triggersOnMap) {
            foreach ($triggersOnMap as $trigger) {
                $cellId = (int) $trigger['CELL_ID'];
                $triggerCoordinates = $this->tileRenderer->coordinate->cellToLatLong($maps[$mapId], $cellId);

                $points[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$triggerCoordinates->longitude, $triggerCoordinates->latitude],
                    ],
                    'properties' => [
                        'id' => $trigger['TRIGGER_ID'],
                        'label' => sprintf('Goto %s', $trigger['ARGUMENTS']),
                        'targetMapId' => (int) explode(',', $trigger['ARGUMENTS'])[0],
                    ],
                ];
            }
        }

        $sender(
            json_encode([
                'type' => 'FeatureCollection',
                'features' => $points,
            ]),
            ['Content-Type' => 'application/json'],
        );
    }
}
