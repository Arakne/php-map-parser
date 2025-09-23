<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\Cell;
use LogicException;

/**
 * Store the map data
 *
 * @todo add other fields
 */
final class Map
{
    /**
     * Attachments to the map, indexed by class name
     *
     * @var array<class-string, object>
     */
    public readonly array $attachments;

    /**
     * Get the X coordinate of the map
     */
    public int $x {
        get => $this->get(MapCoordinates::class)->x ?? throw new LogicException('MapCoordinates are not attached to the map');
    }

    /**
     * Get the Y coordinate of the map
     */
    public int $y {
        get => $this->get(MapCoordinates::class)->y ?? throw new LogicException('MapCoordinates are not attached to the map');
    }

    /**
     * Get the map key (if any)
     */
    public ?string $key {
        get => $this->get(MapKey::class)?->key;
    }

    /**
     * @param array<object> $attachments
     */
    public function __construct(
        public readonly int $id,

        /**
         * @var int<2, max>
         */
        public readonly int $width,

        /**
         * @var int<2, max>
         */
        public readonly int $height,

        /**
         * The background sprite id.
         * If 0, no background is defined.
         */
        public readonly int $background,

        /**
         * @var list<Cell>
         */
        public readonly array $cells,

        /**
         * @var array<object>
         */
        array $attachments = [],
    ) {
        $indexedAttachments = [];

        foreach ($attachments as $attachment) {
            $indexedAttachments[$attachment::class] = $attachment;
        }

        $this->attachments = $indexedAttachments;
    }

    /**
     * Get an attachment by its class name
     *
     * @param class-string<T> $class
     * @return T|null The attached object, or null if not found
     *
     * @template T of object
     */
    public function get(string $class): ?object
    {
        /** @var T|null */
        return $this->attachments[$class] ?? null;
    }
}
