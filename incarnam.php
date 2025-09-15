<?php

set_time_limit(-1);

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\TileRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Tile\Cache\FilesystemTileCache;
use Arakne\MapParser\Tile\MapCoordinates;
use Arakne\MapParser\WorldMap\SwfWorldMap;
use Arakne\MapParser\WorldMap\WorldMapTileRenderer;
use Arakne\Swf\SwfFile;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

require_once __DIR__.'/vendor/autoload.php';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

$areas = $pdo->query('select SUBAREA_ID FROM SUBAREA WHERE AREA_ID = 45')->fetchAll();
$areas = array_map(function ($a) { return $a['SUBAREA_ID']; }, $areas);

$cacheDir = __DIR__.'/cache/incarnam';
$dofusClipsDir = __DIR__.'/gfx';
$dofusMapsDir = '/srv/www/htdocs/dofus/dofus_officiel/maps';

$mapRenderer = new MapRenderer(
    new SwfSpriteRepository(glob($dofusClipsDir.'/g*.swf')),
    new SwfSpriteRepository(glob($dofusClipsDir.'/o*.swf')),
);

$mapLoader = new MapLoader();

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

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

$worldmap = new SwfWorldMap(new SwfFile(__DIR__.'/maps/3.swf'));

$bounds = $worldmap->bounds();
$worldMapRenderer = new WorldMapTileRenderer(
    $worldmap,
    cache: new FilesystemTileCache($cacheDir . '/worldmap'),
);

$tileRenderer = new TileRenderer(
    $mapRenderer,
    function (MapCoordinates $coordinates) use($areas, $dofusMapsDir) {
        static $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');
        static $cache = [];

        if (!array_key_exists($coordinates->x.','.$coordinates->y, $cache)) {
            $pos = [];

            foreach ($areas as $area) {
                $pos[] = '"'.$coordinates->x.','.$coordinates->y.','.$area.'"';
            }


            $cache[$coordinates->x.','.$coordinates->y] = $map = $pdo->query('SELECT * FROM maps WHERE mappos IN ('.implode(',', $pos).')')->fetch();
        } else {
            $map = $cache[$coordinates->x.','.$coordinates->y];
        }

        if (!$map) {
            return null;
        }

        $mapFile = $dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (!is_file($mapFile)) {
            return null;
        }

        return MapStructure::fromSwfFile(new SwfFile($mapFile), $map['key']);
    },
    $bounds->toActualMapBound(),
    scale: 16/15,
    cache: new FilesystemTileCache($cacheDir),
);

$worker = new \Workerman\Worker('http://0.0.0.0:5000');
$worker->count = 8;

$worker->onMessage = function (TcpConnection $connection, Request $request) use ($tileRenderer, $worldMapRenderer, $Xmin, $Xmax, $Ymin, $Ymax): void {
    header('Content-Type: image/png');

    $x = (int) $request->get('x', 0);
    $y = (int) $request->get('y', 0);
    $zoom = (int) $request->get('z', 0);

    $img = $worldMapRenderer->render($x, $y, $zoom);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    if ($zoom >= 6) {
        $img2 = $tileRenderer->render($x, $y, $zoom);
        imagecopy($img, $img2, 0, 0, 0, 0, 256, 256);
    }

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