<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;

use function explode;
use function var_dump;

final class SwfWorldMap
{
    public const int AREA_WIDTH = 600;
    public const int AREA_HEIGHT = 345;

    private ?array $bounds = null;

    private array $cache = [];

    public function __construct(
        private readonly SwfFile $file,
    ) {}

    public function bounds(): array
    {
        if ($this->bounds) {
            return $this->bounds;
        }

        $bounds = [
            'xMin' => 0,
            'yMin' => 0,
            'xMax' => 0,
            'yMax' => 0,
        ];

        foreach (new SwfExtractor($this->file)->exported() as $name => $_) {
            $parts = explode('_', $name, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $x = (int) $parts[0];
            $y = (int) $parts[1];

            if ($x < $bounds['xMin']) {
                $bounds['xMin'] = $x;
            }

            if ($x > $bounds['xMax']) {
                $bounds['xMax'] = $x;
            }

            if ($y < $bounds['yMin']) {
                $bounds['yMin'] = $y;
            }

            if ($y > $bounds['yMax']) {
                $bounds['yMax'] = $y;
            }
        }

        return $this->bounds = $bounds;
    }

    public function area(int $x, int $y): ?string
    {
        $name = $x . '_' . $y;

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        try {
            $sprite = $this->file->assetByName($name);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!$sprite instanceof SpriteDefinition) {
            return null;
        }

        return $this->cache[$name] = new Converter()->toPng($sprite);
    }
}
