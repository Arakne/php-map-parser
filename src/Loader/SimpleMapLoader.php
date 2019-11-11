<?php

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Parser\CellDataParser;

/**
 * Default map loader
 */
final class SimpleMapLoader
{
    /**
     * @var CellDataParser
     */
    private $cellParser;


    /**
     * SimpleMapLoader constructor.
     *
     * @param CellDataParser $cellParser
     */
    public function __construct(CellDataParser $cellParser = null)
    {
        $this->cellParser = $cellParser ?: new CellDataParser();
    }


    /**
     * Creates the map by parsing cells data
     *
     * @param int $id
     * @param int $width
     * @param int $height
     * @param string $cellsData
     *
     * @return Map
     */
    public function load(int $id, int $width, int $height, string $cellsData): Map
    {
        return new Map($id, $width, $height, $this->cellParser->parse($cellsData));
    }
}
