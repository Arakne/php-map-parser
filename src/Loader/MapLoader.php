<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Util\XorCipher;

/**
 * Default map loader
 */
final readonly class MapLoader
{
    public function __construct(
        private CellDataParser $cellParser = new CellDataParser(),
    ) {}

    /**
     * Creates the map by parsing cells data
     */
    public function load(MapStructure $map): Map
    {
        if ($map->encrypted && $map->key !== null) {
            $data = XorCipher::fromHexKey($map->key)->decrypt($map->data);
        } else {
            $data = $map->data;
        }

        return $map->withCells($this->cellParser->parse($data));
    }
}
