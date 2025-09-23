<?php

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\CellShape;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\TileRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Tile\Cache\SqliteCache;
use Arakne\MapParser\Tile\Coordinate\CoordinateSystem;
use Arakne\MapParser\Tile\Coordinate\LatLongBounds;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\TileMapCoordinates;
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
    function (TileMapCoordinates $coordinates) use($dofusMapsDir) {
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

        return MapStructure::fromSwfFile(new SwfFile($mapFile), new MapKey($map['key']));
    },
    minZoomLevel: 7,
    cache: new SqliteCache($cacheDir . '/amakna.db')
);

$incarnamRenderer = new CombinedWorldMapTileRenderer(
    new SwfWorldMap(new SwfFile(__DIR__.'/maps/3.swf')),
    $mapRenderer,
    function (TileMapCoordinates $coordinates) use($dofusMapsDir) {
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

        return MapStructure::fromSwfFile(new SwfFile($mapFile), new MapKey($map['key']));
    },
    minZoomLevel: 6,
    cache: new SqliteCache($cacheDir . '/incarnam.db')
);

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

            $map = new MapLoader()->load(MapStructure::fromSwfFile(new SwfFile($mapFile)), new MapKey($map['key']));

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

            $map = new MapLoader()->load(MapStructure::fromSwfFile(new SwfFile($mapFile)), new MapKey($map['key']));

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
