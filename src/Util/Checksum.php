<?php

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
