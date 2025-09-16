<?php

namespace Arakne\MapParser\WorldMap;

use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Util\Bounds;
use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;
use Override;

use function assert;
use function explode;
use function fopen;
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

        $img = imagecreatefromstring($basePng);
        assert($img !== false);
        $img = imagescale($img, MapRenderer::DISPLAY_WIDTH, MapRenderer::DISPLAY_HEIGHT);
        assert($img !== false);
        imagesavealpha($img, true);

        $out = fopen('php://memory', 'w+');
        assert($out !== false);

        imagepng($img, $out);

        rewind($out);
        $data = stream_get_contents($out);
        assert($data !== false);

        fclose($out);

        return $data;
    }
}
