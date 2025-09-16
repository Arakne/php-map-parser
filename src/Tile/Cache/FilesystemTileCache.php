<?php

namespace Arakne\MapParser\Tile\Cache;

use Arakne\MapParser\Tile\MapCoordinates;
use Closure;
use GdImage;
use Override;

use function dirname;
use function imagecreatefrompng;
use function imagepng;
use function is_dir;
use function is_file;
use function mkdir;

/**
 * Cache implementation using the filesystem, directly storing the PNG files
 */
final readonly class FilesystemTileCache implements TileCacheInterface
{
    public function __construct(
        /**
         * The path where to store the cached tiles
         * It must be writable by the web server
         */
        private string $path,
    ) {}

    #[Override]
    public function map(MapCoordinates $coordinates, Closure $compute): ?GdImage
    {
        $path = 'maps/' . $coordinates->x . '_' . $coordinates->y;

        if ($gd = $this->readFromCache($path)) {
            return $gd;
        }

        $gd = $compute($coordinates);
        $this->writeToCache($path, $gd);

        return $gd;
    }

    #[Override]
    public function fullSizeTile(int $x, int $y, Closure $compute): GdImage
    {
        $path = 'tiles/' . $x . '_' . $y;

        if ($gd = $this->readFromCache($path)) {
            return $gd;
        }

        $gd = $compute($x, $y);
        $this->writeToCache($path, $gd);

        return $gd;
    }

    #[Override]
    public function tile(int $x, int $y, int $zoom, Closure $compute): GdImage
    {
        $path = 'zoom/' . $zoom . '/' . $x . '_' . $y;

        if ($gd = $this->readFromCache($path)) {
            return $gd;
        }

        $gd = $compute($x, $y, $zoom);
        $this->writeToCache($path, $gd);

        return $gd;
    }

    #[Override]
    public function withNamespace(string $namespace): static
    {
        return new self($this->path . '/' . $namespace);
    }

    private function readFromCache(string $path): ?GdImage
    {
        $path = $this->path . '/' . $path . '.png';

        if (is_file($path)) {
            if ($gd = @imagecreatefrompng($path)) {
                return $gd;
            }
        }

        return null;
    }

    private function writeToCache(string $path, ?GdImage $gd): void
    {
        if (!$gd) {
            return;
        }

        $path = $this->path . '/' . $path . '.png';
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        imagepng($gd, $path);
    }
}
