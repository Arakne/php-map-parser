<?php

use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\TileRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Tile\Cache\FilesystemTileCache;
use Arakne\MapParser\Tile\MapCoordinates;
use Arakne\MapParser\WorldMap\CombinedWorldMapTileRenderer;
use Arakne\MapParser\WorldMap\SwfWorldMap;
use Arakne\Swf\SwfFile;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

set_time_limit(-1);

require_once __DIR__.'/vendor/autoload.php';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

$areas = $pdo->query('select SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 0)')->fetchAll();
$areas = array_map(function ($a) { return $a['SUBAREA_ID']; }, $areas);

$cacheDir = __DIR__.'/cache/amakna';
$dofusClipsDir = __DIR__.'/gfx';
$dofusMapsDir = '/srv/www/htdocs/dofus/dofus_officiel/maps';

$mapRenderer = new MapRenderer(
    new SwfSpriteRepository(glob($dofusClipsDir.'/g*.swf')),
    new SwfSpriteRepository(glob($dofusClipsDir.'/o*.swf'))
);
/*
$Xmin = 0;
$Xmax = 0;
$Ymin = 0;
$Ymax = 0;

$stmt = $pdo->prepare('select mappos from maps where mappos like ?');

foreach ($areas as $area) {
    $stmt->execute(['%,%,'.$area]);

    while ($map = $stmt->fetch()) {
        $pos = array_map('intval', explode(',', $map['mappos']));

        if ($pos[0] < $Xmin) {
            $Xmin = $pos[0];
        }

        if ($pos[0] > $Xmax) {
            $Xmax = $pos[0];
        }

        if ($pos[1] < $Ymin) {
            $Ymin = $pos[1];
        }

        if ($pos[1] > $Ymax) {
            $Ymax = $pos[1];
        }
    }
}

$x = $_GET['x'] ?? 0;
$y = $_GET['y'] ?? 0;
$zoom = $_GET['z'] ?? 0;

$tileRenderer = new TileRenderer(
    $mapRenderer,
    function (MapCoordinates $coordinates) use($areas, $pdo, $dofusMapsDir) {
        $pos = [];

        foreach ($areas as $area) {
            $pos[] = '"'.$coordinates->x.','.$coordinates->y.','.$area.'"';
        }

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');
        $map = $pdo->query('SELECT * FROM maps WHERE mappos IN ('.implode(',', $pos).')')->fetch();

        if (!$map) {
            return null;
        }

        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (!is_file($mapFile)) {
            return null;
        }

        return MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
    },
    $Xmin,
    $Xmax,
    $Ymin,
    $Ymax,
    scale: 16/15,
    cache: new \Arakne\MapParser\Tile\Cache\FilesystemTileCache($cacheDir),
);*/

$tileRenderer = new CombinedWorldMapTileRenderer(
    new SwfWorldMap(new SwfFile(__DIR__.'/maps/0.swf')),
    $mapRenderer,
    function (MapCoordinates $coordinates) use($areas, $pdo, $dofusMapsDir) {
        $pos = [];

        foreach ($areas as $area) {
            $pos[] = '"'.$coordinates->x.','.$coordinates->y.','.$area.'"';
        }

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');
        $map = $pdo->query('SELECT * FROM maps WHERE mappos IN ('.implode(',', $pos).')')->fetch();

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
    cache: new FilesystemTileCache($cacheDir),
);

$worker = new \Workerman\Worker('http://0.0.0.0:5000');
$worker->count = 16;

$worker->onMessage = function (TcpConnection $connection, Request $request) use ($tileRenderer, $Xmin, $Xmax, $Ymin, $Ymax): void {
    header('Content-Type: image/png');

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
