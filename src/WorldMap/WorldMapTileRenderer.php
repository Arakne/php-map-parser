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
use Arakne\MapParser\Tile\BaseTileRenderer;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\TileMapCoordinates;
use GdImage;

use function assert;
use function imagecreatefromstring;
use function imagesavealpha;

/**
 * Render dofus world maps as square tiles compatible with leaflet or other tile-based map viewers.
 */
final class WorldMapTileRenderer extends BaseTileRenderer
{
    /**
     * The world map background color on the client, in hex format
     */
    public const string BACKGROUND_COLOR = '#E5E5B9';

    /**
     * @param WorldMapInterface $worldMap
     * @param positive-int $tileSize {@see BaseTileRenderer::$tileSize}
     * @param TileCacheInterface $cache The cache to use for storing rendered chunks and tiles
     */
    public function __construct(
        /**
         * The world map to render
         */
        private readonly WorldMapInterface $worldMap,
        int $tileSize = self::TILE_SIZE,
        TileCacheInterface $cache = new NullTileCache(),
    ) {
        parent::__construct(
            $this->renderChunk(...),
            $cache->bounds($worldMap->bounds(...)),
            mapWidth: MapRenderer::DISPLAY_WIDTH,
            mapHeight: MapRenderer::DISPLAY_HEIGHT,
            tileSize: $tileSize,
            cache: $cache,
        );
    }

    private function renderChunk(TileMapCoordinates $coordinates): ?GdImage
    {
        if (!$map = $this->worldMap->chunk($coordinates->x, $coordinates->y)) {
            return null;
        }

        $img = imagecreatefromstring($map);
        assert($img !== false);

        imagesavealpha($img, true);

        return $img;
    }
}
