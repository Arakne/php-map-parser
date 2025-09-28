# Dofus map parser in PHP

[![Build](https://github.com/Arakne/php-map-parser/actions/workflows/build.yml/badge.svg)](https://github.com/Arakne/php-map-parser/actions/workflows/build.yml)
[![Packagist](https://img.shields.io/packagist/v/arakne/php-map-parser)](https://packagist.org/packages/arakne/php-map-parser)
[![codecov](https://codecov.io/github/Arakne/php-map-parser/graph/badge.svg?token=vrelSdfWkp)](https://codecov.io/github/Arakne/php-map-parser)
[![License](https://img.shields.io/github/license/Arakne/php-map-parser)](./COPYING.LESSER)

Parse Dofus retro maps from SWF files and render them as images, map tiles, or simply provide the map data in a structured format.

## Installation and requirements

This library relies on [Arakne-Swf](https://github.com/Arakne/ArakneSwf), so it has the same requirements:
- PHP 8.4 or higher
- The `gd` and `Imagick` extension for image rendering
- The `zlib` extension for compressed SWF files
- `rsvg` or `Inkscape` installed (or embedded in Imagick) for SVG rendering
- `composer` to install the library

> [!TIP]
> To improve performance, it's recommended to enable JIT on both CLI and FPM SAPIs.

The library uses Dofus client's files, so you need to download them first and make them available to your application.

After that, you can install the library using composer:

```bash
composer require arakne/php-map-parser
```

## Usage

### With the facade class

The class [`DofusMapParser`](src/DofusMapParser.php) provides a simple interface to parse and render maps, which is suitable for most use cases.

#### Create the facade
```php
use Arakne\MapParser\DofusMapParser;
use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Tile\Cache\SqliteTileCache;
use Arakne\MapParser\Sprite\Cache\SqliteSpriteCache;
use Arakne\MapParser\Loader\MapKey;

// Configure paths
const DOFUS_CLIENT_PATH = '/path/to/dofus/client';

// Maps path is not required: if not set, maps will be loaded from the client path
const SERVER_MAP_PATH = '/srv/www/htdocs/dofus/maps';

const CACHE_DIR = __DIR__ . '/var/cache'

// Database can be used to get maps from coordinates and also get map keys
const DB_DSN = 'mysql:host=localhost;dbname=dofus';
const DB_USER = 'dofus';
const DB_PASSWORD = 'dofus';

// Instantiate the facade
$parser = new DofusMapParser(
    dofusPath: DOFUS_CLIENT_PATH,
    mapsPath: SERVER_MAP_PATH,
    
    // Configure map resolver from coordinates: this is used only if you want to use world map using leaflet for example
    mapByCoordinates: function (MapCoordinates $coordinates, int $superAreaId) {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);

        // SQL query to get the map ID from coordinates and super area
        // In this example, Araknemu database structure is used
        $query = <<<'SQL'
            SELECT id FROM maps 
            WHERE MAP_X = ? AND MAP_Y = ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = ?))
            SQL
        ;

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(1, $coordinates->x, PDO::PARAM_INT);
        $stmt->bindValue(2, $coordinates->y, PDO::PARAM_INT);
        $stmt->bindValue(3, $superAreaId, PDO::PARAM_INT);
        $stmt->execute();

        $map = $stmt->fetch();

        if (!$map) {
            return null;
        }

        return (int) $map['id'];
    },
    
    // Configure cache. Rendering tiles is expensive, so caching them is recommended
    // SQLite provides good performances without flooding the filesystem with thousands of files
    tileCache: new SqliteTileCache(CACHE_DIR . '/tiles.db'),
    spriteCache: new SqliteSpriteCache(CACHE_DIR . '/sprites.db'),
    
    // Attachments will load extra data from the database (or any other sources)
    // This is required to load map key and coordinates
    attachmentsProviders: [
        function (MapStructure $map) {
            // Load map properties from the database
            // This example uses Araknemu database structure
            $query = 'SELECT * FROM maps WHERE id = ?';
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);

            $stmt = $pdo->prepare($query);
            $stmt->bindValue(1, $map->id, PDO::PARAM_INT);
            $stmt->execute();

            $map = $stmt->fetch();

            if (!$map) {
                return [];
            }

            // Any other attachments can be returned here as well
            // There will be accessible using $map->get(AttachmentClass::class)
            // So you can attach for example a list of NPCs, monsters, resources, etc.
            return [new MapKey($map['key']), new MapCoordinates($map['MAP_X'], $map['MAP_Y'], $map['SUBAREA_ID'])];
        },
    ],
);
```

#### Parse a map and iterate its cells

```php
/** @var \Arakne\MapParser\DofusMapParser $parser */
// Load and parse the map with ID 37
$map = $parser->parse(37);

// Now you can access to all cells properties
foreach ($map->cells as $id => $cell) {
    echo "Cell {$id}\n";
    echo "  Ground: {$cell->ground->number}\n";
    echo "  Layer1: {$cell->layer1->number}\n";
    echo "  Layer2: {$cell->layer2->number}\n";
}
```

#### Render the map as an image

```php
/** @var \Arakne\MapParser\DofusMapParser $parser */
// Directly render the map as an image
$img = $parser->render(37);

// Render returns a GD image object, so you must use GD functions to manipulate or save it
imagepng($img, 'map37.png');
```

#### Render worldmap with leaflet

Create a simple template with leaflet to display the world map and load tiles on demand.
```php
<!DOCTYPE html>
<html>
<head>
    <title>Dofus Map</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
          integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
          crossorigin=""/>
</head>
<body>
<div id="mapid" style="height: calc(100vh - 30px); width: 100vw; background-color: <?= WorldMapTileRenderer::BACKGROUND_COLOR ?>;"></div>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
        integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
        crossorigin=""></script>
        <script lang="js">
    var mymap = L.map('mapid', {
        zoomSnap: 1,
        zoomDelta: 1,
        wheelPxPerZoomLevel: 120,
    }).setView([70, -40], 4);

    L.tileLayer('http://127.0.0.1:5000/tiles?x={x}&y={y}&z={z}', {
        // maxZoom represents original Dofus map size, so 1 pixel on map = 1 pixel on screen
        // We add +1 to allow zooming one step further, so tiles are scaled up
        maxZoom: <?= $tiles->maxZoom + 1 ?>,
    }).addTo(mymap);
</body>
</html>
```

Now you can create a simple tile server to serve map tiles on demand:
```php
/** @var \Arakne\MapParser\DofusMapParser $parser */

// Get the tile renderer, here for amakna world map
$tiles = $parser->amaknaWorldMap();

// Warmup the cache using a CLI command
// php index.php warmup
// This will take some time (more than 1 hour) but will speed up tile rendering
// The tile renderer can be used without warmup and even in parallel of the warmup process
if (($argv[1] ?? null) === 'warmup') {
    $amaknaRenderer->warmup(
        function (string $name, int $current, int $total) {
            echo sprintf("Building %s (%d/%d)\n", $name, $current, $total);
        },
    );
    exit(0);
}

$page = trim($_SERVER['PATH_INFO'] ?? '', '/');

switch ($page) {
    case '':
        // Show the template with leaflet
        require __DIR__ . '/template.php';
        break;
        
    case 'tiles':
        // Get tile coordinates from the query string
        // Note: no validation is done here, so be sure to validate the input in a real application
        $x = (int) ($_GET['x'] ?? 0);
        $y = (int) ($_GET['y'] ?? 0);
        $z = (int) ($_GET['z'] ?? 0);
        
        // Render the tile
        $tile = $tiles->render($x, $y, $z);
        
        // Send the image to the browser
        header('Content-Type: image/png');
        imagepng($tile);
        break;
        
    default:
        http_response_code(404);
        echo "Not found";
        break;
}
```

## License

This library is shared under the [LGPLv3](./COPYING.LESSER) license.
