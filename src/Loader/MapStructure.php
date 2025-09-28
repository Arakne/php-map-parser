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

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;

use function max;
use function str_ends_with;
use function substr;

/**
 * Raw map data structure
 * This structure is not yet parsed, and represents the raw data as found in the swf.
 */
final readonly class MapStructure
{
    public function __construct(
        /**
         * The map id
         */
        public int $id,

        /**
         * Width of the map in cells
         *
         * The width must be at least 2 cells because the first half of the first cell
         * and the second half of the last cell are cropped.
         *
         * @var int<2, max>
         */
        public int $width,

        /**
         * Height of the map in cells
         *
         * The width must be at least 2 cells because the first half of the first cell
         * and the second half of the last cell are cropped.
         *
         * @var int<2, max>
         */
        public int $height,

        /**
         * The raw cell data as a string
         *
         * In case of encrypted map, this data is hexadecimal encrypted data with a length of width * height * 20 characters.
         * In case of unencrypted map, this data is a pseudo-Base64" string with a length of width * height * 10 characters.
         */
        public string $data,

        /**
         * The background number
         *
         * If 0, no background is set.
         * This number corresponds to a sprite in ground swf files (g*.swf).
         */
        public int $background = 0,

        /**
         * The ambiance music id
         * If 0, no ambiance is set.
         */
        public int $ambiance = 0,

        /**
         * The music id
         * If 0, no music is set.
         */
        public int $music = 0,

        /**
         * True if the map is outdoor, false if indoor
         *
         * Note: this value is often incorrect in the SWF files, so it should not be trusted.
         */
        public bool $outdoor = true,

        /**
         * Map capabilities flags using bitfield
         */
        public int $capabilities = 0,

        /**
         * Does the map data is encrypted?
         *
         * This is usually determined by the SWF file name, if it ends with "X.swf" it is encrypted.
         */
        public bool $encrypted = false,

        /**
         * The version of the map.
         *
         * Usually this is an integer string like "0706131721",
         * but the integer value is not enforced by the client, so it can be any string.
         *
         * The version is extracted from the SWF file name, after the "_" and before the ".swf".
         */
        public ?string $version = null,

        /**
         * List of attachments that will be passed to the MapLoader when parsing the map.
         *
         * @var array<object>
         */
        public array $attachments = [],
    ) {}

    /**
     * Instantiate the {@see Map} object with the given cells.
     *
     * @param list<Cell> $cells List of parsed cells
     * @param array<object> $attachments Optional attachments that may be used by the map
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
     * @param object ...$attachments Optional attachments that may be used during parsing.
     *
     * @return self
     */
    public static function fromSwfFile(SwfFile $file, object ...$attachments): self
    {
        // 20ko max
        if (!$file->valid(20_000)) {
            throw new InvalidArgumentException('SWF file is not valid');
        }

        $content = $file->variables();

        if (!isset($content['id'], $content['width'], $content['height'], $content['mapData'])) {
            throw new InvalidArgumentException('SWF file does not contain a valid map structure');
        }

        $encrypted = str_ends_with($file->path, 'X.swf');

        if (($pos = strrpos($file->path, '_')) !== false) {
            $version = substr($file->path, $pos + 1, $encrypted ? -5 : -4);
        } else {
            $version = null;
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
            $encrypted,
            $version,
            $attachments,
        );
    }
}
