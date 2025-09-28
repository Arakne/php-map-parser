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

use Arakne\MapParser\Parser\CellDataParser;
use InvalidArgumentException;

use function array_push;
use function array_values;

/**
 * Default map loader
 */
final readonly class MapLoader
{
    public function __construct(
        private CellDataParser $cellParser = new CellDataParser(),

        /**
         * List of attachments providers to add to the map
         *
         * @var array<callable(MapStructure):list<object>>
         */
        private array $attachmentsProviders = [],
    ) {}

    /**
     * Creates the map by parsing cells data
     *
     * Usage:
     * ```php
     * $loader = new MapLoader();
     *
     * // Simple map (not encrypted)
     * $map = $loader->load(MapStructure::fromSwf(new SwfFile('maps/1234_789.swf')));
     *
     * // Add attachments
     * $map = $loader->load(
     *     MapStructure::fromSwf(new SwfFile('maps/1234_789.swf')),
     *     new MapCoordinates(x: 1234, y: 789),
     *     new MyCustomAttachment(...),
     * );
     *
     * // Encrypted map: use MapKey as attachment to decrypt it
     * $map = $loader->load(
     *    MapStructure::fromSwf(new SwfFile('maps/1234_789X.swf')),
     *    new MapKey('0123456789abcdef'), // The key to decrypt the map
     * );
     * ```
     *
     * @param MapStructure $map The map structure to load
     * @param object ...$attachments Additional attachments to add to the map
     *
     * @throws InvalidArgumentException When the map is encrypted but no MapKey attachment is provided
     */
    public function load(MapStructure $map, object ...$attachments): Map
    {
        $attachments[] = $map; // Always add the map structure as attachment
        array_push($attachments, ...array_values($map->attachments));

        foreach ($this->attachmentsProviders as $provider) {
            array_push($attachments, ...$provider($map));
        }

        $data = $map->data;

        if ($map->encrypted) {
            $hasKey = false;

            foreach ($attachments as $attachment) {
                if ($attachment instanceof MapKey) {
                    $data = $attachment->decrypt($map->data);
                    $hasKey = true;
                    break;
                }
            }

            if (!$hasKey) {
                throw new InvalidArgumentException('The map is encrypted, a MapKey attachment is required to decrypt it.');
            }
        }

        return $map->withCells(
            $this->cellParser->parse($data),
            $attachments,
        );
    }
}
