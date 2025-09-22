<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;

use function max;
use function str_ends_with;

/**
 * Raw map data structure
 * This structure is not yet parsed, and represents the raw data as found in the swf.
 */
final readonly class MapStructure
{
    public function __construct(
        public int $id,

        /**
         * @var int<2, max>
         */
        public int $width,

        /**
         * @var int<2, max>
         */
        public int $height,
        public string $data,
        public int $background = 0,
        public int $ambiance = 0,
        public int $music = 0,
        public bool $outdoor = true,
        public int $capabilities = 0,
        public bool $encrypted = false,

        // @todo remove from here
        public ?string $key = null,
    ) {}

    /**
     * Instantiate the {@see Map} object with the given cells.
     *
     * @param list<Cell> $cells
     * @param array<object> $attachments
     *
     * @return Map
     */
    public function withCells(array $cells, array $attachments = []): Map
    {
        return new Map(
            $this->id,
            $this->width,
            $this->height,
            $this->background,
            $cells,
            $attachments,
        );
    }

    /**
     * Parse the SWF file and extract the map structure.
     *
     * @param SwfFile $file The SWF file to parse.
     * @param string|null $key The decryption key, if the map is encrypted. The key must be encoded in hex.
     *
     * @return self
     */
    public static function fromSwfFile(SwfFile $file, ?string $key = null): self
    {
        // 20ko max
        if (!$file->valid(20_000)) {
            throw new InvalidArgumentException('SWF file is not valid');
        }

        $content = $file->variables();

        if (!isset($content['id'], $content['width'], $content['height'], $content['mapData'])) {
            throw new InvalidArgumentException('SWF file does not contain a valid map structure');
        }

        return new MapStructure(
            (int) $content['id'],
            max((int) $content['width'], 2),
            max((int) $content['height'], 2),
            (string) $content['mapData'],
            (int) ($content['backgroundNum'] ?? 0),
            (int) ($content['ambianceId'] ?? 0),
            (int) ($content['musicId'] ?? 0),
            (bool) ($content['bOutdoor'] ?? true),
            (int) ($content['capabilities'] ?? 0),
            encrypted: str_ends_with($file->path, 'X.swf'),
            key: $key,
        );
    }
}
