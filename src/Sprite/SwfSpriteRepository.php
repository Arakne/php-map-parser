<?php

namespace Arakne\MapParser\Sprite;

use Arakne\MapParser\Sprite\Cache\InMemorySpriteCache;
use Arakne\MapParser\Sprite\Cache\SpriteCacheInterface;
use Arakne\Swf\Extractor\Drawer\Converter\Converter;
use Arakne\Swf\Extractor\Sprite\SpriteDefinition;
use Arakne\Swf\Extractor\SwfExtractor;
use Arakne\Swf\SwfFile;
use Override;

use function is_numeric;

/**
 * Base implementation of {@see SpriteRepositoryInterface} with direct SWF file usage.
 *
 * @psalm-api
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

    public function __construct(
        /**
         * Path of swf files
         *
         * @var list<string>
         */
        private readonly array $files,
        private readonly SpriteCacheInterface $cache = new InMemorySpriteCache(10),
    ) {}

    #[Override]
    public function get(int $id): Sprite
    {
        return $this->cache->sprite($id, $this->doLoadSprite(...));
    }

    private function doLoadSprite(int $id): Sprite
    {
        if (($swf = $this->getExtractorForId($id)) === null) {
            return Sprite::invalid($id, SpriteState::Missing);
        }

        try {
            $sprite = $swf->byName((string) $id);

            if (!$sprite instanceof SpriteDefinition) {
                return Sprite::invalid($id, SpriteState::Invalid);
            }

            $bounds = $sprite->bounds();
            $converter = new Converter();

            $width = ($bounds->width() / 20);
            $height = ($bounds->height() / 20);

            if ($width < 1 || $height < 1) {
                // Less than 1px
                return Sprite::invalid($id, SpriteState::Empty);
            }

            return new Sprite(
                id: $id,
                pngData: $converter->toPng($sprite),
                width: $width,
                height: $height,
                offsetX: ($bounds->xmin / 20),
                offsetY: ($bounds->ymin / 20),
                state: SpriteState::Valid,
            );
        } finally {
            $swf->releaseIfOutOfMemory();
        }
    }

    private function getExtractorForId(int $id): ?SwfExtractor
    {
        $this->exportMap ??= $this->cache->exports(
            function () {
                $exports = [];

                foreach ($this->files as $file) {
                    $extractor = $this->getExtractor($file);

                    foreach ($extractor->exported() as $exportId => $_) {
                        if (is_numeric($exportId)) {
                            $exports[(int) $exportId] = $file;
                        }
                    }
                }

                return $exports;
            },
        );

        return isset($this->exportMap[$id]) ? $this->getExtractor($this->exportMap[$id]) : null;
    }

    private function getExtractor(string $path): SwfExtractor
    {
        return $this->extractors[$path] ??= new SwfExtractor(new SwfFile($path));
    }
}
