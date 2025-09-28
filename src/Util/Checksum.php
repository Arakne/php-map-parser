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

namespace Arakne\MapParser\Util;

use function ord;
use function strlen;

/**
 * Implementation of the Dofus network checksum
 *
 * https://github.com/Emudofus/Dofus/blob/1.29/dofus/aks/Aks.as#L248
 */
final readonly class Checksum
{
    /**
     * Compute the checksum as integer
     * The returned value is in interval [0-15]
     *
     * @param string $value Value to compute
     *
     * @return int<0, 15> The checksum of value
     */
    public static function integer(string $value): int
    {
        $checksum = 0;
        $len = strlen($value);

        for ($i = 0; $i < $len; ++$i) {
            $checksum += ord($value[$i]) % 16;
        }

        return $checksum % 16;
    }
}
