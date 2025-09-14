<?php

namespace Arakne\MapParser\Sprite;

use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use Override;

use function is_numeric;

/**
 * Base implementation of {@see SpriteRepositoryInterface} with direct SWF file usage.
 */
final class SwfSpriteRepository implements SpriteRepositoryInterface
{
    /**
     * Map of exported sprite ID to SWF file path
     *
     * @var array<int, string>|null
     */
    private ?array $exportMap = null;

    /**
     * Active SWF extractors, indexed by file path
     *
     * @var array<string, SwfExtractor>
     */
    private array $extractors = [];

    /**
     * @todo to remove when cache implementation is added
     * @var array<int, Sprite>
     */
    private array $spriteCache = [];

    public function __construct(
        /**
         * Path of swf files
         *
         * @var list<string>
         */
        private readonly array $files,

        // @todo inject cache implementation (custom interface, not PSR-6/16)
    ) {}

    #[Override]
    public function get(int $id): Sprite
    {
        // @todo external cache implementation
        if ($cached = $this->spriteCache[$id] ?? null) {
            return $cached;
        }

        if (($swf = $this->getExtractorForId($id)) === null) {
            return $this->spriteCache[$id] = Sprite::invalid($id, SpriteState::Missing);
        }

        try {
            $sprite = $swf->byName((string) $id);

            if (!$sprite instanceof SpriteDefinition) {
                return $this->spriteCache[$id] = Sprite::invalid($id, SpriteState::Invalid);
            }

            $bounds = $sprite->bounds();
            $converter = new Converter();

            if ($bounds->width() < 20 || $bounds->height() < 20) {
                // Less than 1px
                return $this->spriteCache[$id] = Sprite::invalid($id, SpriteState::Empty);
            }

            return $this->spriteCache[$id] = new Sprite(
                id: $id,
                pngData: $converter->toPng($sprite),
                width: (int) ($bounds->width() / 20),
                height: (int) ($bounds->height() / 20),
                offsetX: (int) ($bounds->xmin / 20),
                offsetY: (int) ($bounds->ymin / 20),
                state: SpriteState::Valid,
            );
        } finally {
            $swf->releaseIfOutOfMemory();
        }
    }

    private function getExtractorForId(int $id): ?SwfExtractor
    {
        if ($this->exportMap === null) {
            $this->exportMap = [];

            foreach ($this->files as $file) {
                $extractor = $this->getExtractor($file);

                foreach ($extractor->exported() as $exportId => $_) {
                    if (is_numeric($exportId)) {
                        $this->exportMap[(int) $exportId] = $file;
                    }
                }
            }
        }

        return isset($this->exportMap[$id]) ? $this->getExtractor($this->exportMap[$id]) : null;
    }

    private function getExtractor(string $path): SwfExtractor
    {
        return $this->extractors[$path] ??= new SwfExtractor(new SwfFile($path));
    }
}
