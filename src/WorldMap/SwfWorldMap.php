<?php

/*
 * This file is part of PHP Map parser.
 *
 * PHP Map parser is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * PHP Map parser is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with PHP Map parser.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2019-2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;
use Override;

use function assert;
use function explode;
use function fopen;
use function imagealphablending;
use function imagecopyresampled;
use function imagecreatetruecolor;
use function imagepng;
use function imagesavealpha;
use function is_numeric;
use function rewind;
use function stream_get_contents;

/**
 * Implementation of WorldMapInterface using a SWF file as source
 */
final class SwfWorldMap implements WorldMapInterface
{
    private ?Bounds $bounds = null;
    private SwfExtractor $extractor {
        get => $this->extractor ??= new SwfExtractor($this->file);
    }

    public function __construct(
        private readonly SwfFile $file,
        private readonly Converter $converter = new Converter(),
    ) {}

    #[Override]
    public function bounds(): Bounds
    {
        if ($this->bounds) {
            return $this->bounds;
        }

        $xMin = 0;
        $yMin = 0;
        $xMax = 0;
        $yMax = 0;

        foreach ($this->extractor->exported() as $name => $_) {
            $parts = explode('_', $name, 2);

            if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                continue;
            }

            $x = (int) $parts[0];
            $y = (int) $parts[1];

            if ($x < $xMin) {
                $xMin = $x;
            }

            if ($x > $xMax) {
                $xMax = $x;
            }

            if ($y < $yMin) {
                $yMin = $y;
            }

            if ($y > $yMax) {
                $yMax = $y;
            }
        }

        return $this->bounds = new Bounds($xMin, $xMax, $yMin, $yMax);
    }

    #[Override]
    public function chunk(int $x, int $y): ?string
    {
        $name = $x . '_' . $y;

        try {
            $sprite = $this->extractor->byName($name);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!$sprite instanceof SpriteDefinition) {
            return null;
        }

        $basePng = $this->converter->toPng($sprite);

        $baseImage = imagecreatefromstring($basePng);
        assert($baseImage !== false);
        imagesavealpha($baseImage, true);

        $resized = imagecreatetruecolor(MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        assert($resized !== false);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        // Old GD version doesn't support well transparency on imagescale, so we use imagecopyresampled instead
        imagecopyresampled($resized, $baseImage, 0, 0, 0, 0, MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT, imagesx($baseImage), imagesy($baseImage));

        $out = fopen('php://memory', 'w+');
        assert($out !== false);

        imagepng($resized, $out);

        rewind($out);
        $data = stream_get_contents($out);
        assert($data !== false);

        fclose($out);

        return $data;
    }
}
