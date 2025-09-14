<?php

namespace Arakne\MapParser\Renderer\Tile;

final class MapCoordinates
{
    public function __construct(
        /**
         * The X coordinate of the map
         *
         * @psalm-api
         */
        public int $x,

        /**
         * The Y coordinate of the map
         *
         * @psalm-api
         */
        public int $y,

        /**
         * X Offset in pixels of the rendered map on the tile
         */
        public int $xSourceOffset = 0,

        /**
         * Y Offset in pixels of the rendered map on the tile
         */
        public int $ySourceOffset = 0,

        /**
         * X position in pixels on the tile where to draw the map
         */
        public int $xDestinationOffset = 0,

        /**
         * Y position in pixels on the tile where to draw the map
         */
        public int $yDestinationOffset = 0,
    ) {}
}
