<?php

set_time_limit(-1);

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\Tile\MapCoordinates;
use Arakne\MapParser\Renderer\Tile\TileRenderer;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
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

//function mapByCoords($x, $y) {
//    global $areas, $pdo, $cacheDir, $mapRenderer, $mapLoader, $dofusMapsDir;
//
//    $query = 'SELECT * FROM maps WHERE mappos IN (';
//
//    $pos = [];
//
//    foreach ($areas as $area) {
//        $pos[] = '"'.$x.','.$y.','.$area.'"';
//    }
//
//    $map = $pdo->query('SELECT * FROM maps WHERE mappos IN ('.implode(',', $pos).')')->fetch();
//
//    if (!$map) {
//        return null;
//    }
//
//    $map = $mapLoader->fromSwfFile(new SwfFile($dofusMapsDir . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf'), $map['key']);
//
//    //if (!file_exists($cacheDir.'/'.$map['id'].'.png')) {
//        $img = $mapRenderer->render($map);
//        ob_start();
//        imagepng($img/*, $cacheDir.'/'.$map->id.'.png'*/);
//
//        return ob_get_clean();
//        //$img->save($cacheDir.'/'.$map['id'].'.png');
//    //}
//
//    //return $cacheDir.'/'.$map->id.'.png';
//}

$tileRenderer = new TileRenderer(
    $mapRenderer,
    function (MapCoordinates $coordinates) use($areas, $dofusMapsDir) {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu', 'araknemu');

        $pos = [];

        foreach ($areas as $area) {
            $pos[] = '"'.$coordinates->x.','.$coordinates->y.','.$area.'"';
        }

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
    $Ymin,
);

$worker = new \Workerman\Worker('http://0.0.0.0:5000');
$worker->count = 8;

$worker->onMessage = function (TcpConnection $connection, Request $request) use ($tileRenderer, $Xmin, $Xmax, $Ymin, $Ymax): void {
    header('Content-Type: image/png');

    $x = (int) $request->get('x', 0);
    $y = (int) $request->get('y', 0);
    $zoom = (int) $request->get('z', 0);

    // Simple tile renderer (max zoom)
    /*$gd = $tileRenderer->render(
        (int) $request->get('x', 3),
        (int) $request->get('y', 3),
    );

    ob_start();
    imagepng($gd);
    $imageData = ob_get_clean();

    $connection->send(
        new Response(
            headers: ['Content-Type' => 'image/png'],
            body: $imageData,
        )
    );*/

    $realWidth = ($Xmax - $Xmin + 1) * MapRenderer::DISPLAY_WIDTH;
    $realHeight = ($Ymax - $Ymin + 1) * MapRenderer::DISPLAY_HEIGHT;

    $size = max($realWidth, $realHeight);
    $size = pow(2, ceil(log($size, 2)));

    // @todo 2^n
    $tileCount = ceil($size / TileRenderer::TILE_SIZE);
    $tileCount /= pow(2, $zoom);
    $tileCount = (int) $tileCount;

    $startX = (int) ($x * $tileCount);
    $startY = (int) ($y * $tileCount);

    $img = imagecreatetruecolor(TileRenderer::TILE_SIZE, TileRenderer::TILE_SIZE);
    $subtileSize = TileRenderer::TILE_SIZE / $tileCount;

    for ($x = 0; $x <= $tileCount; ++$x) {
        for ($y = 0; $y <= $tileCount; ++$y) {
            $gd = $tileRenderer->render($startX + $x, $startY + $y);

            imagecopyresampled($img, $gd, $x * $subtileSize, $y * $subtileSize, 0, 0, $subtileSize, $subtileSize, TileRenderer::TILE_SIZE, TileRenderer::TILE_SIZE);
        }
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