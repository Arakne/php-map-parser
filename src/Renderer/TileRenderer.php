<?php

namespace Arakne\MapParser\Renderer;

/**
 * Class TileRenderer
 */
class TileRenderer
{
    /**
     * @var MapRenderer
     */
    private $renderer;

    /**
     * @var callable
     */
    private $mapResolver;

    /**
     * @var int
     */
    private $tileWidth = 256;

    /**
     * @var int
     */
    private $tileHeight = 256;

    /**
     * @var int
     */
    private $Xmin = 0;

    /**
     * @var int
     */
    private $Ymin = 0;

    /**
     * @var string|null
     */
    private $cacheDir;

    /**
     * TileRenderer constructor.
     *
     * @param MapRenderer $renderer
     * @param callable $mapResolver
     * @param int $Xmin
     * @param int $Ymin
     * @param string|null $cacheDir
     */
    public function __construct(MapRenderer $renderer, callable $mapResolver, int $Xmin, int $Ymin, ?string $cacheDir)
    {
        $this->renderer = $renderer;
        $this->mapResolver = $mapResolver;
        $this->Xmin = $Xmin;
        $this->Ymin = $Ymin;
        $this->setCacheDir($cacheDir);
    }

    public function setCacheDir(?string $cacheDir): self
    {
        $this->cacheDir = $cacheDir;

        if (!$cacheDir) {
            return $this;
        }

        foreach (['maps', 'tiles'] as $dir) {
            if (!is_dir($cacheDir . DIRECTORY_SEPARATOR . $dir)) {
                mkdir($cacheDir . DIRECTORY_SEPARATOR . $dir, 0777, true);
            }
        }

        return $this;
    }

    public function setTileSize(int $width, int $height = null): self
    {
        if ($height === null) {
            $height = $width;
        }

        $this->tileWidth = $width;
        $this->tileHeight = $height;

        return $this;
    }

    /**
     * @return MapCoordinates[]
     */
    public function toMapCoordinates(int $x, int $y): array
    {
        $mapX = floor($x * $this->tileWidth / MapRenderer::DISPLAY_WIDTH);
        $mapY = floor($y * $this->tileHeight / MapRenderer::DISPLAY_HEIGHT);

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

    public function render(int $x, int $y)
    {
        $cacheFile = $this->tileCacheFile($x, $y);

        if (file_exists($cacheFile)) {
            return imagecreatefrompng($cacheFile);
        }

        $img = imagecreatetruecolor($this->tileWidth, $this->tileHeight);

        foreach ($this->toMapCoordinates($x, $y) as $mapCoordinate) {
            if (!$map = $this->renderMap($mapCoordinate)) {
                continue;
            }

            imagecopy(
                $img,
                $map,
                $mapCoordinate->xDestinationOffset(),
                $mapCoordinate->yDestinationOffset(),
                $mapCoordinate->xSourceOffset(),
                $mapCoordinate->ySourceOffset(),
                MapRenderer::DISPLAY_WIDTH,
                MapRenderer::DISPLAY_HEIGHT
            );
            imagedestroy($map);
        }

        if ($cacheFile) {
            imagepng($img, $cacheFile);
        }

        return $img;
    }

    private function renderMap(MapCoordinates $coordinates)
    {
        if (!$map = ($this->mapResolver)($coordinates)) {
            return null;
        }

        $cacheFile = $this->mapCacheFile($map['id']);

        if ($cacheFile && file_exists($cacheFile)) {
            return imagecreatefrompng($cacheFile);
        }

        $img = $this->renderer->render($map['mapData'], $map['width'], $map['height']);

        if ($cacheFile) {
            imagepng($img, $cacheFile);
        }

        return $img;
    }

    private function mapCacheFile(int $mapId): ?string
    {
        if (!$this->cacheDir) {
            return null;
        }

        return $this->cacheDir . DIRECTORY_SEPARATOR . 'maps' . DIRECTORY_SEPARATOR . $mapId . '.png';
    }

    private function tileCacheFile(int $x, int $y): ?string
    {
        if (!$this->cacheDir) {
            return null;
        }

        return $this->cacheDir . DIRECTORY_SEPARATOR . 'tiles' . DIRECTORY_SEPARATOR . $x . '_' . $y . '.png';
    }
}

class MapCoordinates
{
    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;

    /**
     * @var int
     */
    private $xSourceOffset;

    /**
     * @var int
     */
    private $ySourceOffset;

    /**
     * @var int
     */
    private $xDestinationOffset;

    /**
     * @var int
     */
    private $yDestinationOffset;

    /**
     * MapCoordinates constructor.
     *
     * @param int $x
     * @param int $y
     * @param int $xSourceOffset
     * @param int $ySourceOffset
     * @param int $xDestinationOffset
     * @param int $yDestinationOffset
     */
    public function __construct(int $x, int $y, int $xSourceOffset = 0, int $ySourceOffset = 0, int $xDestinationOffset = 0, int $yDestinationOffset = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->xSourceOffset = $xSourceOffset;
        $this->ySourceOffset = $ySourceOffset;
        $this->xDestinationOffset = $xDestinationOffset;
        $this->yDestinationOffset = $yDestinationOffset;
    }

    /**
     * @return int
     */
    public function x(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function y(): int
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function xSourceOffset(): int
    {
        return $this->xSourceOffset;
    }

    /**
     * @return int
     */
    public function ySourceOffset(): int
    {
        return $this->ySourceOffset;
    }

    /**
     * @return int
     */
    public function xDestinationOffset(): int
    {
        return $this->xDestinationOffset;
    }

    /**
     * @return int
     */
    public function yDestinationOffset(): int
    {
        return $this->yDestinationOffset;
    }
}
