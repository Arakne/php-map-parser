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

namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Loader\MapLoader;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Tile\BaseTileRenderer;
use Arakne\MapParser\Tile\Cache\NullTileCache;
use Arakne\MapParser\Tile\Cache\TileCacheInterface;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\TileMapCoordinates;
use Closure;
use GdImage;

/**
 * Render dofus maps as square tiles compatible with leaflet or other tile-based map viewers.
 *
 * @psalm-api
 */
final class TileRenderer extends BaseTileRenderer
{
    /**
     * @param Bounds $bounds The world map coordinates bounds
     * @param float $scale The scale to apply to each map (default: 1.0)
     * @param positive-int $tileSize The tile size in pixels (default: 256)
     * @param TileCacheInterface $cache The cache to use for storing rendered maps and tiles
     */
    public function __construct(
        /**
         * The map renderer to use for rendering each map
         */
        private readonly MapRendererInterface $renderer,

        /**
         * Resolve the map from the [X,Y] coordinates
         *
         * @var Closure(TileMapCoordinates):(MapStructure|null)
         */
        private readonly Closure $mapResolver,
        Bounds $bounds,
        float $scale = 1.0,
        int $tileSize = self::TILE_SIZE,
        TileCacheInterface $cache = new NullTileCache(),
        private readonly MapLoader $loader = new MapLoader(),
    ) {
        parent::__construct(
            $this->doRenderMap(...),
            $bounds,
            $scale,
            MapRenderer::DISPLAY_WIDTH,
            MapRenderer::DISPLAY_HEIGHT,
            $tileSize,
            $cache,
        );
    }

    private function doRenderMap(TileMapCoordinates $coordinates): ?GdImage
    {
        if (!$map = ($this->mapResolver)($coordinates)) {
            return null;
        }

        $map = $this->loader->load($map);

        return $this->renderer->render($map);
    }
}
