<?php

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Tile\Cache\SqliteCache;
use Arakne\MapParser\Tile\MapCoordinates;
use Arakne\MapParser\Util\BBox;
use Arakne\MapParser\Util\Bounds;
use Arakne\MapParser\WorldMap\CombinedWorldMapTileRenderer;
use Arakne\MapParser\WorldMap\SwfWorldMap;
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

$mapRenderer = new MapRenderer(
    new SwfSpriteRepository(glob($dofusClipsDir.'/g*.swf')),
    new SwfSpriteRepository(glob($dofusClipsDir.'/o*.swf'))
);

$amaknaRenderer = new CombinedWorldMapTileRenderer(
    new SwfWorldMap(new SwfFile(__DIR__.'/maps/0.swf')),
    $mapRenderer,
    function (MapCoordinates $coordinates) use($dofusMapsDir) {
        $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X = ? AND MAP_Y = ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 0))
            SQL
        ;

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $coordinates->x, PDO::PARAM_INT);
        $stmt->bindValue(2, $coordinates->y, PDO::PARAM_INT);
        $stmt->execute();

        $map = $stmt->fetch();

        if (!$map) {
            return null;
        }

        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (!is_file($mapFile)) {
            return null;
        }

        return MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
    },
    minZoomLevel: 7,
    cache: new SqliteCache($cacheDir . '/amakna.db')
);

$incarnamRenderer = new CombinedWorldMapTileRenderer(
    new SwfWorldMap(new SwfFile(__DIR__.'/maps/3.swf')),
    $mapRenderer,
    function (MapCoordinates $coordinates) use($dofusMapsDir) {
        $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X = ? AND MAP_Y = ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 3))
            SQL
        ;

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $coordinates->x, PDO::PARAM_INT);
        $stmt->bindValue(2, $coordinates->y, PDO::PARAM_INT);
        $stmt->execute();

        $map = $stmt->fetch();

        if (!$map) {
            return null;
        }

        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (!is_file($mapFile)) {
            return null;
        }

        return MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
    },
    minZoomLevel: 6,
    cache: new SqliteCache($cacheDir . '/incarnam.db')
);

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

    $maps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $structures = [];

    foreach ($maps as $map) {
        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (is_file($mapFile)) {
            $structures[] = MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
        }
    }

    return $structures;
}
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
    $structures = [];

    foreach ($maps as $map) {
        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (is_file($mapFile)) {
            $structures[] = MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
        }
    }

    return $structures;
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

function mapCoordinates(int $mapId): array
{
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');
    $stmt = $pdo->prepare('SELECT * FROM maps WHERE id = ?');
    $stmt->execute([$mapId]);
    $map = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$map) {
        throw new RuntimeException('Map not found: ' . $mapId);
    }

    return [(int)$map['MAP_X'], (int)$map['MAP_Y']];
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

$worker->onMessage = function (TcpConnection $connection, Request $request) use ($dofusMapsDir, $mapRenderer, $amaknaRenderer, $incarnamRenderer): void {
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
            $bbox = BBox::fromString($request->get('bbox'));

            $bounds = $incarnamRenderer->bounds->inBBox($bbox, $incarnamRenderer->maxZoom);
            $maps = incarnamMapsInBounds($bounds);
            $triggers = loadTriggers(array_map(fn (MapStructure $m) => $m->id, $maps));

            $cellShapes = [];
            foreach ($maps as $map) {
                $cellShapes[$map->id] = CellShape::fromMap(new MapLoader()->load($map));
            }

            $points = [];

            foreach ($triggers as $mapId => $triggersOnMap) {
                foreach ($triggersOnMap as $trigger) {
                    $cellId = (int) $trigger['CELL_ID'];
                    $triggerCell = $cellShapes[$mapId][$cellId] ?? null;

                    if (!$triggerCell) {
                        continue;
                    }

                    [$mapX, $mapY] = mapCoordinates($mapId);

                    $mapX -= $incarnamRenderer->bounds->xMin;
                    $mapY -= $incarnamRenderer->bounds->yMin;
                    $mapX *= MapRenderer::DISPLAY_WIDTH;
                    $mapY *= MapRenderer::DISPLAY_HEIGHT;

                    $pointInPixelX = ($mapX + $triggerCell->x) * 16 / 15;
                    $pointInPixelY = ($mapY + $triggerCell->y) * 16 / 15;

                    $points[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => pixelsToLongLat($pointInPixelX, $pointInPixelY, $incarnamRenderer->maxZoom),
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

        case '/markers/amakna':
            $bbox = BBox::fromString($request->get('bbox'));

            $bounds = $amaknaRenderer->bounds->inBBox($bbox, $amaknaRenderer->maxZoom);
            $maps = amaknaMapsInBounds($bounds);
            $triggers = loadTriggers(array_map(fn (MapStructure $m) => $m->id, $maps));

            $cellShapes = [];
            foreach ($maps as $map) {
                $cellShapes[$map->id] = CellShape::fromMap(new MapLoader()->load($map));
            }

            $points = [];

            foreach ($triggers as $mapId => $triggersOnMap) {
                foreach ($triggersOnMap as $trigger) {
                    $cellId = (int) $trigger['CELL_ID'];
                    $triggerCell = $cellShapes[$mapId][$cellId] ?? null;

                    if (!$triggerCell) {
                        continue;
                    }

                    [$mapX, $mapY] = mapCoordinates($mapId);

                    $mapX -= $amaknaRenderer->bounds->xMin;
                    $mapY -= $amaknaRenderer->bounds->yMin;
                    $mapX *= MapRenderer::DISPLAY_WIDTH;
                    $mapY *= MapRenderer::DISPLAY_HEIGHT;

                    $pointInPixelX = ($mapX + $triggerCell->x) * 16 / 15;
                    $pointInPixelY = ($mapY + $triggerCell->y) * 16 / 15;

                    $points[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => pixelsToLongLat($pointInPixelX, $pointInPixelY, $amaknaRenderer->maxZoom),
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

        case '/showmap':
            $mapId = (int) $request->get('id', 0);
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');
            $stmt = $pdo->prepare('SELECT * FROM maps WHERE id = ?');
            $stmt->execute([$mapId]);
            $map = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$map) {
                $connection->send(new Response(404, body: 'Map not found'));
                return;
            }

            $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

            if (!is_file($mapFile)) {
                $connection->send(new Response(404, body: 'Map not found'));
                return;
            }

            $map = MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
            $map = new MapLoader()->load($map);

            ob_start();
            imagepng($mapRenderer->render($map));
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

function latLongToLeaflet(float $lat, float $long, int $zoom): array
{
    $latRad = deg2rad($lat);
    $n = 2 ** $zoom;
    $xTile = (int)(($long + 180.0) / 360.0 * $n);
    $yTile = (int)((1.0 - log(tan($latRad) + (1 / cos($latRad))) / M_PI) / 2.0 * $n);

    return [$xTile, $yTile];
}

function leafletToLatLong(int $xTile, int $yTile, int $zoom): array
{
    $n = 2 ** $zoom;
    $lon = $xTile / $n * 360.0 - 180.0;
    $latRad = atan(sinh(M_PI * (1 - 2 * $yTile / $n)));
    $lat = rad2deg($latRad);

    return [$lat, $lon];
}

function pixelsToLongLat(int $pixelX, int $pixelY, int $zoom): array
{
    $n = 2 ** $zoom * 256;
    $lon = $pixelX / $n * 360.0 - 180.0;
    $latRad = atan(sinh(M_PI * (1 - 2 * $pixelY / $n)));
    $lat = rad2deg($latRad);

    return [$lon, $lat];
}

\Workerman\Worker::runAll();
