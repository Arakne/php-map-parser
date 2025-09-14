<?php

namespace Arakne\MapParser\Renderer\Tile;

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\MapRendererInterface;
use Closure;
use GdImage;

use function imagecopy;
use function imagecreatetruecolor;

/**
 * Class TileRenderer
 */
final class TileRenderer
{
    public const int TILE_SIZE = 256;

    public function __construct(
        private readonly MapRendererInterface $renderer,

        /**
         * Resolve the map from the [X,Y] coordinates
         *
         * @var Closure(MapCoordinates):MapStructure
         */
        private readonly Closure $mapResolver,

        /**
         * The minimal X coordinate of the map set
         */
        private readonly int $Xmin,

        /**
         * The minimal Y coordinate of the map set
         */
        private readonly int $Ymin,

        /**
         * @var positive-int
         */
        private readonly int $tileWidth = self::TILE_SIZE,

        /**
         * @var positive-int
         */
        private readonly int $tileHeight = self::TILE_SIZE,
    ) {}

    /**
     * Convert tile coordinates to map coordinates
     * Because tiles can overlap multiple maps, this function can return multiple map coordinates
     *
     * @return MapCoordinates[]
     */
    public function toMapCoordinates(int $x, int $y): array
    {
        $mapX = (int) ($x * $this->tileWidth / MapRenderer::DISPLAY_WIDTH);
        $mapY = (int) ($y * $this->tileHeight / MapRenderer::DISPLAY_HEIGHT);

        $Xoffset = ($x * $this->tileWidth) - ($mapX * MapRenderer::DISPLAY_WIDTH);
        $Yoffset = ($y * $this->tileHeight) - ($mapY * MapRenderer::DISPLAY_HEIGHT);

        $map = new MapCoordinates($mapX + $this->Xmin, $mapY + $this->Ymin, $Xoffset, $Yoffset);

        $maps = [$map];

        $hasX = ($x + 1) * $this->tileHeight > ($mapX + 1) * MapRenderer::DISPLAY_WIDTH;
        $hasY = ($y + 1) * $this->tileWidth > ($mapY + 1) * MapRenderer::DISPLAY_HEIGHT;

        if ($hasX) {
            $maps[] = new MapCoordinates($mapX + $this->Xmin + 1, $mapY + $this->Ymin, 0, $Yoffset, MapRenderer::DISPLAY_WIDTH - $Xoffset, 0);
        }

        if ($hasY) {
            $maps[] = new MapCoordinates($mapX + $this->Xmin, $mapY + $this->Ymin + 1, $Xoffset, 0, 0, MapRenderer::DISPLAY_HEIGHT - $Yoffset);
        }

        if ($hasX && $hasY) {
            $maps[] = new MapCoordinates($mapX + $this->Xmin + 1, $mapY + $this->Ymin + 1, 0, 0, MapRenderer::DISPLAY_WIDTH - $Xoffset, MapRenderer::DISPLAY_HEIGHT - $Yoffset);
        }

        return $maps;
    }

    /**
     * Render a single tile at the given [X,Y] coordinates
     * Coordinates are in tile space, not map space
     *
     * @param int $x
     * @param int $y
     *
     * @return GdImage
     */
    public function render(int $x, int $y): GdImage
    {
        $img = imagecreatetruecolor($this->tileWidth, $this->tileHeight);

        foreach ($this->toMapCoordinates($x, $y) as $mapCoordinate) {
            if (!$map = $this->renderMap($mapCoordinate)) {
                continue;
            }

            imagecopy(
                $img,
                $map,
                $mapCoordinate->xDestinationOffset,
                $mapCoordinate->yDestinationOffset,
                $mapCoordinate->xSourceOffset,
                $mapCoordinate->ySourceOffset,
                MapRenderer::DISPLAY_WIDTH,
                MapRenderer::DISPLAY_HEIGHT
            );
        }

        return $img;
    }

    private function renderMap(MapCoordinates $coordinates): ?GdImage
    {
        if (!$map = ($this->mapResolver)($coordinates)) {
            return null;
        }

        $mapLoader = new MapLoader();
        $map = $mapLoader->load($map);

        return $this->renderer->render($map);
    }
}
