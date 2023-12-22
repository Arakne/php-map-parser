<?php

set_time_limit(-1);

use Arakne\MapParser\Renderer\MapRenderer;
use Swf\Cli\Jar;
use Swf\SwfLoader;

require_once __DIR__.'/vendor/autoload.php';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=araknemu', 'araknemu');

$areas = $pdo->query('select SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = 0)')->fetchAll();
$areas = array_map(function ($a) { return $a['SUBAREA_ID']; }, $areas);

$cacheDir = __DIR__.'/cache/amakna';
$dofusClipsDir = __DIR__.'/gfx';

$swfLoader = new SwfLoader(new Jar(__DIR__.'/ffdec_15.1.1/ffdec.jar'));

$mapRenderer = new MapRenderer(
    $swfLoader->bulk(glob($dofusClipsDir.'/g*.swf')),
    $swfLoader->bulk(glob($dofusClipsDir.'/o*.swf'))
);

if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

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

function mapByCoords($x, $y) {
    global $areas, $pdo, $cacheDir, $mapRenderer;

    $query = 'SELECT * FROM maps WHERE mappos IN (';

    $pos = [];

    foreach ($areas as $area) {
        $pos[] = '"'.$x.','.$y.','.$area.'"';
    }

    $map = $pdo->query('SELECT * FROM maps WHERE mappos IN ('.implode(',', $pos).')')->fetch();

    if (!$map) {
        return null;
    }

    if (!file_exists($cacheDir.'/'.$map['id'].'.png')) {
        $img = $mapRenderer->render($map['mapData'], 15, 17);
        imagepng($img, $cacheDir.'/'.$map['id'].'.png');
        imagedestroy($img);
    }

    return $cacheDir.'/'.$map['id'].'.png';
}

$zoom = $_GET['z'] ?? 0;

$x = $_GET['x'] ?? 0;
$y = $_GET['y'] ?? 0;

if (!is_dir($cacheDir.'/area')) {
    mkdir($cacheDir.'/area', 0777, true);
}

$areaFile = $cacheDir.'/area/'.$x.'_'.$y.'_'.$zoom.'.png';

if (file_exists($areaFile)) {
    header('Content-Type: image/png');
    readfile($areaFile);
    exit;
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

// $tileCount should be 2^n, so get the closest 2^n
$tileCount = pow(2, ceil(log($tileCount, 2)));
$tileCount /= pow(2, $zoom);

$startX = $x * $tileCount;
$startY = $y * $tileCount;

$img = imagecreatetruecolor(TILE_SIZE, TILE_SIZE);
$subtileSize = TILE_SIZE / $tileCount;

// @todo disallow out of bounds
if (
    $x >= 2 ** $zoom
    || $y >= 2 ** $zoom
    || ($zoom == 1 && $y > 0) // @todo ne devrait pas être là
) {
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

for ($x = 0; $x <= $tileCount; ++$x) {
    for ($y = 0; $y <= $tileCount; ++$y) {
        $mapTile = $tileRenderer->render($startX + $x, $startY + $y);
        $gd = $mapTile->getCore();

        imagecopyresampled($img, $gd, $x * $subtileSize, $y * $subtileSize, 0, 0, $subtileSize, $subtileSize, 256, 256);
        //imagedestroy($mapTile);
    }
}

// Save cache
imagepng($img, $areaFile);

header('Content-Type: image/png');
imagepng($img);
imagedestroy($img);
