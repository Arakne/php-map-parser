<?php

namespace Arakne\MapParser\Parser;

use Arakne\MapParser\Util\Base64;

/**
 * Parser for cells data string
 */
final class CellDataParser
{
    /**
     * Cache the parser cell, indexed by the data string
     *
     * @var Cell[]
     */
    private $cache = [];


    /**
     * Parse the cells data
     *
     * @param string $data
     *
     * @return Cell[]
     */
    public function parse(string $data): array
    {
        $cells = [];

        foreach (str_split($data, 10) as $part) {
            if (isset($this->cache[$part])) {
                $cells[] = $this->cache[$part];
                continue;
            }

            $cellData = [];

            for ($i = 0; $i < 10; ++$i) {
                $cellData[$i] = Base64::ord($part[$i]);
            }

            $cells[] = $this->cache[$part] = new Cell($cellData);
        }

        return $cells;
    }
}
