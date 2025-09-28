<?php

namespace Example\Worldmap;

use Arakne\MapParser\DofusMapParser;
use Arakne\MapParser\Sprite\Cache\SqliteSpriteCache;
use Arakne\MapParser\Tile\Cache\SqliteTileCache;
use Arakne\MapParser\Tile\TileRendererInterface;
use Closure;

use function strtolower;

final class Application
{
    public readonly MapRepository $repository;
    public readonly DofusMapParser $mapParser;

    public private(set) TileRendererInterface $incarnam {
        get => $this->incarnam ??= $this->mapParser->incarnamWorldMap();
    }

    public private(set) TileRendererInterface $amakna {
        get => $this->amakna ??= $this->mapParser->amaknaWorldMap();
    }

    private array $routes = [];

    public function __construct(
        public readonly Config $config,
    ) {
        $this->repository = new MapRepository($this->config);

        $this->mapParser = new DofusMapParser(
            dofusPath: $this->config->dofusPath,
            mapsPath: $this->config->mapsPath,
            mapByCoordinates: $this->repository->findByMapByCoordinates(...),
            tileCache: new SqliteTileCache($this->config->cachePath . '/tiles.db'),
            spriteCache: new SqliteSpriteCache($this->config->cachePath . '/sprites.db'),
            attachmentsProviders: [
                $this->repository->loadMapAttachments(...),
            ],
        );

        $this->routes = [
            '/' => fn () => new WorldMapController($this->amakna, 'amakna'),
            '/amakna' => fn () => new WorldMapController($this->amakna, 'amakna'),
            '/incarnam' => fn () => new WorldMapController($this->incarnam, 'incarnam'),
            '/tiles/amakna' => fn () => new RenderTileController($this->amakna),
            '/tiles/incarnam' => fn () => new RenderTileController($this->incarnam),
            '/markers/amakna' => fn () => new LoadMarkersController($this->mapParser, $this->amakna, $this->repository, DofusMapParser::AMAKNA_SUPERAREA_ID),
            '/markers/incarnam' => fn () => new LoadMarkersController($this->mapParser, $this->incarnam, $this->repository, DofusMapParser::INCARNAM_SUPERAREA_ID),
            '/showmap' => fn () => new ShowMapController($this->mapParser, $this->repository),
            '/render' => fn () => new RenderMapController($this->mapParser),
        ];
    }

    public function run(string $path, array $get, Closure $sender): void
    {
        $path = '/' . strtolower(trim($path, '/'));
        $route = $this->routes[$path] ?? null;

        if ($route) {
            ($route())($get, $sender);
        } else {
            $sender('404 Not Found');
        }
    }

    public function warmup(): void
    {
        echo "Warming up Amakna...\n";
        $this->amakna->warmup(
            function (string $name, int $current, int $total) {
                echo sprintf("[Amakna] Building %s (%d/%d)\n", $name, $current, $total);
            },
        );
        echo "Warming up Incarnam...\n";
        $this->incarnam->warmup(
            function (string $name, int $current, int $total) {
                echo sprintf("[Incarnam] Building %s (%d/%d)\n", $name, $current, $total);
            },
        );
        echo "Done.\n";
    }
}
