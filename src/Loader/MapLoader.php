<?php

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
        // @todo attachment providers
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
        array_push($attachments, ...array_values($map->attachments));

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
