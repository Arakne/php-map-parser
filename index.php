<?php

use Arakne\MapParser\DofusMapParser;
use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Tile\Cache\SqliteCache;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\Coordinate\LatLongBounds;
use Arakne\Swf\SwfFile;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

set_time_limit(-1);
ini_set('memory_limit', '1G');

require_once __DIR__.'/vendor/autoload.php';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

$cacheDir = __DIR__.'/cache';
$dofusClipsDir = __DIR__.'/gfx';
$dofusMapsDir = '/srv/www/htdocs/dofus/dofus_officiel/maps';
$dofusClientDir = '/home/vincent/.local/app/Dofus';

$dmp = new DofusMapParser(
    dofusPath: $dofusClientDir,
    mapsPath: $dofusMapsDir,
    mapByCoordinates: function (MapCoordinates $coordinates, int $superAreaId) use ($dofusMapsDir) {
        $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X = ? AND MAP_Y = ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = ?))
            SQL
        ;

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $coordinates->x, PDO::PARAM_INT);
        $stmt->bindValue(2, $coordinates->y, PDO::PARAM_INT);
        $stmt->bindValue(3, $superAreaId, PDO::PARAM_INT);
        $stmt->execute();

        $map = $stmt->fetch();

        if (!$map) {
            return null;
        }

        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (!is_file($mapFile)) {
            return null;
        }

        return MapStructure::fromSwfFile(new SwfFile($mapFile), new MapKey($map['key']), new MapCoordinates($map['MAP_X'], $map['MAP_Y'], $map['SUBAREA_ID']));
    },
    tileCache: new SqliteCache($cacheDir . '/tiles.db'),
    spriteCache: new \Arakne\MapParser\Sprite\Cache\SqliteSpriteCache($cacheDir . '/sprites.db'),
    attachmentsProviders: [
        function (MapStructure $map) {
            if ($map->attachments) {
                return [];
            }

            $query = 'SELECT * FROM maps WHERE id = ?';
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

            $stmt = $pdo->prepare($query);
            $stmt->bindValue(1, $map->id, PDO::PARAM_INT);
            $stmt->execute();

            $map = $stmt->fetch();

            if (!$map) {
                return [];
            }

            return [new MapKey($map['key']), new MapCoordinates($map['MAP_X'], $map['MAP_Y'], $map['SUBAREA_ID'])];
        }
    ]
);

$amaknaRenderer = $dmp->amaknaWorldMap(7);
$incarnamRenderer = $dmp->incarnamWorldMap(6);

/**
 * @param Bounds $bounds
 * @return array<int, Map>
 */
function incarnamMapsInBounds(Bounds $bounds): array
{
    global $dofusMapsDir;

    $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X BETWEEN ? AND ?
            AND MAP_Y BETWEEN ? AND ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 3))
            SQL
    ;

    $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(1, $bounds->xMin, PDO::PARAM_INT);
    $stmt->bindValue(2, $bounds->xMax, PDO::PARAM_INT);
    $stmt->bindValue(3, $bounds->yMin, PDO::PARAM_INT);
    $stmt->bindValue(4, $bounds->yMax, PDO::PARAM_INT);
    $stmt->execute();

    $loader = new MapLoader();
    $maps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $loaded = [];

    foreach ($maps as $map) {
        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (is_file($mapFile)) {
            $loaded[$map['id']] = $loader->load(
                MapStructure::fromSwfFile(new SwfFile($mapFile)),
                new MapCoordinates($map['MAP_X'], $map['MAP_Y']),
                new MapKey($map['key']),
            );
        }
    }

    return $loaded;
}

/**
 * @param Bounds $bounds
 * @return array<int, Map>
 */
function amaknaMapsInBounds(Bounds $bounds): array
{
    global $dofusMapsDir;

    $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X BETWEEN ? AND ?
            AND MAP_Y BETWEEN ? AND ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 0))
            SQL
    ;

    $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(1, $bounds->xMin, PDO::PARAM_INT);
    $stmt->bindValue(2, $bounds->xMax, PDO::PARAM_INT);
    $stmt->bindValue(3, $bounds->yMin, PDO::PARAM_INT);
    $stmt->bindValue(4, $bounds->yMax, PDO::PARAM_INT);
    $stmt->execute();

    $maps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $loader = new MapLoader();
    $loadedMaps = [];

    foreach ($maps as $map) {
        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (is_file($mapFile)) {
            $loadedMaps[$map['id']] = $loader->load(
                MapStructure::fromSwfFile(new SwfFile($mapFile)),
                new MapCoordinates($map['MAP_X'], $map['MAP_Y']),
                new MapKey($map['key']),
            );
        }
    }

    return $loadedMaps;
}

function loadTriggers(array $mapIds): array
{
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');
    $stmt = $pdo->prepare('SELECT * FROM MAP_TRIGGER WHERE MAP_ID = ?');
    $triggers = [];

    foreach ($mapIds as $mapId) {
        $stmt->execute([$mapId]);
        $triggers[$mapId] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $triggers;
}

if (($argv[1] ?? null) === 'warmup') {
    echo "Warming up Amakna...\n";
    $amaknaRenderer->warmup(
        function (string $name, int $current, int $total) {
            echo sprintf("[Amakna] Building %s (%d/%d)\n", $name, $current, $total);
        },
    );
    echo "Warming up Incarnam...\n";
    $incarnamRenderer->warmup(
        function (string $name, int $current, int $total) {
            echo sprintf("[Incarnam] Building %s (%d/%d)\n", $name, $current, $total);
        },
    );
    echo "Done.\n";
    exit(0);
}

$worker = new \Workerman\Worker('http://0.0.0.0:5000');
$worker->count = 16;

$worker->onMessage = function (TcpConnection $connection, Request $request) use ($dofusMapsDir, $amaknaRenderer, $incarnamRenderer): void {
    global $dmp;
    switch ($request->path()) {
        case '/':
        case '/amakna':
            ob_start();
            $maxZoom = $amaknaRenderer->maxZoom + 1;
            $map = 'amakna';
            include __DIR__.'/worldmap.html.php';
            $connection->send(new Response(body: ob_get_clean(), headers: ['Content-Type' => 'text/html']));
            return;
        case '/incarnam':
            ob_start();
            $maxZoom = $incarnamRenderer->maxZoom + 1;
            $map = 'incarnam';
            include __DIR__.'/worldmap.html.php';
            $connection->send(new Response(body: ob_get_clean(), headers: ['Content-Type' => 'text/html']));
            return;
        case '/tiles/amakna':
            $tileRenderer = $amaknaRenderer;
            break;
        case '/tiles/incarnam':
            $tileRenderer = $incarnamRenderer;
            break;

        case '/markers/incarnam':
            $bbox = LatLongBounds::fromString($request->get('bbox'));
            $bounds = $incarnamRenderer->coordinate->toMapBounds($bbox);
            $maps = incarnamMapsInBounds($bounds);
            $triggers = loadTriggers(array_map(fn (Map $m) => $m->id, $maps));

            $points = [];

            foreach ($triggers as $mapId => $triggersOnMap) {
                foreach ($triggersOnMap as $trigger) {
                    $cellId = (int) $trigger['CELL_ID'];
                    $triggerCoordinates = $incarnamRenderer->coordinate->cellToLatLong($maps[$mapId], $cellId);

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
                            'minZoom' => 6,
                        ],
                    ];
                }
            }

            $connection->send(new Response(
                headers: ['Content-Type' => 'application/json'],
                body: json_encode([
                    'type' => 'FeatureCollection',
                    'features' => [
                        ...$points,
                    ],
                ]),
            ));
            return;

        case '/markers/amakna':
            $bbox = LatLongBounds::fromString($request->get('bbox'));
            $bounds = $amaknaRenderer->coordinate->toMapBounds($bbox);
            $maps = amaknaMapsInBounds($bounds);
            $triggers = loadTriggers(array_map(fn (Map $m) => $m->id, $maps));
            $points = [];

            foreach ($triggers as $mapId => $triggersOnMap) {
                foreach ($triggersOnMap as $trigger) {
                    $cellId = (int) $trigger['CELL_ID'];
                    $triggerCell = $amaknaRenderer->coordinate->cellToLatLong($maps[$mapId], $cellId);

                    if (!$triggerCell) {
                        continue;
                    }

                    $points[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [$triggerCell->longitude, $triggerCell->latitude],
                        ],
                        'properties' => [
                            'id' => $trigger['TRIGGER_ID'],
                            'label' => sprintf('Goto %s', $trigger['ARGUMENTS']),
                            'targetMapId' => (int) explode(',', $trigger['ARGUMENTS'])[0],
                            'minZoom' => 6,
                        ],
                    ];
                }
            }

            $connection->send(new Response(
                headers: ['Content-Type' => 'application/json'],
                body: json_encode([
                    'type' => 'FeatureCollection',
                    'features' => [
                        ...$points,
                    ],
                ]),
            ));
            return;

        case '/showmap':
            $mapId = (int) $request->get('id', 0);
            $map = $dmp->load($mapId);

            $triggers = array_map(function ($trigger) use ($map) {
                $cell = CellShape::fromCellId($map, (int)$trigger['CELL_ID']);

                return [
                    'x' => $cell->x,
                    'y' => $cell->y,
                    'target' => (int) explode(',', $trigger['ARGUMENTS'])[0],
                ];
            }, loadTriggers([$mapId])[$mapId] ?? []);

            $width = MapRenderer::DISPLAY_WIDTH;
            $height = MapRenderer::DISPLAY_HEIGHT;

            ob_start();
            require __DIR__ . '/map.html.php';
            $content = ob_get_clean();

            $connection->send(new Response(body: $content));
            return;

        case '/render':
            $mapId = (int) $request->get('id', 0);

            $rendered = $dmp->render($mapId);

            ob_start();
            imagepng($rendered);
            $imageData = ob_get_clean();

            $connection->send(new Response(
                headers: ['Content-Type' => 'image/png'],
                body: $imageData,
            ));
            return;

        default:
            $connection->send(new Response(404, body: 'Not found'));
            return;
    }

    $x = (int) $request->get('x', 0);
    $y = (int) $request->get('y', 0);
    $zoom = (int) $request->get('z', 0);

    $img = $tileRenderer->render($x, $y, $zoom);

    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();

    $connection->send(
        new Response(
            headers: ['Content-Type' => 'image/png'],
            body: $imageData,
        )
    );
};

\Workerman\Worker::runAll();
