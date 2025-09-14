<?php

set_time_limit(-1);

use Arakne\MapParser\Renderer\MapRenderer;
use Swf\Cli\Jar;
use Swf\SwfLoader;

require_once __DIR__.'/vendor/autoload.php';

/*
 * Setup section
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu');

$cacheDir = __DIR__.'/cache/amakna';
$dofusClipsDir = __DIR__.'/gfx';

$swfLoader = new SwfLoader(new Jar(__DIR__.'/ffdec_15.1.1/ffdec.jar'));

$mapRenderer = new MapRenderer(
    $swfLoader->bulk(glob($dofusClipsDir.'/g*.swf'))->setResultDirectory($cacheDir.'/grounds'),
    $swfLoader->bulk(glob($dofusClipsDir.'/o*.swf'))->setResultDirectory($cacheDir.'/objects')
);

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$maps = $pdo->query('select * from maps')->fetchAll(PDO::FETCH_ASSOC);
$mapsCacheDir = $cacheDir.'/maps';
$mapLoader = new \Arakne\MapParser\Loader\MapLoader();

if (!is_dir($mapsCacheDir)) {
    mkdir($mapsCacheDir, 0777, true);
}

/*
 * Build maps cache
 */

foreach ($maps as $i => $map) {
    $mapId = $map['id'];
    $start = microtime(true);

    if (!file_exists($mapsCacheDir.'/'.$mapId.'.png')) {
        try {
            $img = $mapRenderer->render($mapLoader->load(
                $map['id'],
                $map['width'],
                $map['height'],
                $map['mapData']
            ));
            $img->save($mapsCacheDir . '/' . $mapId . '.png');
        } catch (Throwable $e) {
            echo 'Map '.$mapId.' failed: '.$e->getMessage().PHP_EOL;
            continue;
        }
    }

    echo 'Map '.$mapId.' done in ' . (microtime(true) - $start) . 's (' . ($i + 1) . '/' . count($maps) . ')' . PHP_EOL;
    flush();
}

/*
 * Build tiles cache
 */

$areas = $pdo->query('select SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 0)')->fetchAll();
$areas = array_map(function ($a) { return $a['SUBAREA_ID']; }, $areas);

const TILE_SIZE = 256;
const WH_RATE = MapRenderer::DISPLAY_WIDTH / MapRenderer::DISPLAY_HEIGHT;

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

$tileRenderer = new \Arakne\MapParser\Renderer\TileRenderer(
    $mapRenderer,
    function (\Arakne\MapParser\Renderer\MapCoordinates $coordinates) use($areas, $pdo) {
        $query = 'SELECT * FROM maps WHERE mappos IN (';

        $pos = [];

        foreach ($areas as $area) {
            $pos[] = '"'.$coordinates->x().','.$coordinates->y().','.$area.'"';
        }

        return $pdo->query('SELECT * FROM maps WHERE mappos IN ('.implode(',', $pos).')')->fetch();
    },
    $Xmin,
    $Ymin,
    $cacheDir
);

$width = $Xmax - $Xmin + 1;
$height = $Ymax - $Ymin + 1;

$realWidth = ($Xmax - $Xmin) * MapRenderer::DISPLAY_WIDTH;
$realHeight = ($Ymax - $Ymin) * MapRenderer::DISPLAY_HEIGHT;

$size = max($realWidth, $realHeight);

// @todo 2^n
$tileCount = ceil($size / TILE_SIZE);

$startX = 0;
$startY = 0;

$subtileSize = TILE_SIZE / $tileCount;

$count = $tileCount * $tileCount;
$current = 0;

for ($x = 0; $x <= $tileCount; ++$x) {
    for ($y = 0; $y <= $tileCount; ++$y) {
        $mapTile = $tileRenderer->render($startX + $x, $startY + $y);
        $mapTile->destroy();

        echo 'Tile '.++$current.'/'.$count.' done'.PHP_EOL;
    }
}
