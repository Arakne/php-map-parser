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

namespace Arakne\MapParser\Parser;

use Arakne\MapParser\Util\Base64;

use function str_split;

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
    private array $cache = [];

    /**
     * Parse the cells data
     *
     * @param string $data
     *
     * @return list<Cell>
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
                $cellData[] = Base64::ord($part[$i]);
            }

            $cells[] = $this->cache[$part] = new Cell($cellData);
        }

        return $cells;
    }
}
