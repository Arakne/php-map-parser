<?php

namespace Arakne\MapParser\Util;

use InvalidArgumentException;

use function ord;
use function str_repeat;
use function strlen;

/**
 * Utility class for Dofus Pseudo base 64
 */

final class Base64
{
    private const array CHARSET = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
        'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-', '_'
    ];

    /**
     * Get int value of base64 char
     *
     * @param string $c Char to convert
     *
     * @return int
     * @psalm-api
     */
    static public function ord(string $c): int
    {
        if ($c >= 'a' && $c <= 'z') {
            return ord($c) - ord('a');
        }

        if ($c >= 'A' && $c <= 'Z') {
            return ord($c) - ord('A') + 26;
        }

        if ($c >= '0' && $c <= '9') {
            return ord($c) - ord('0') + 52;
        }

        return match ($c) {
            '-' => 62,
            '_' => 63,
            default => throw new InvalidArgumentException('Invalid char value'),
        };
    }

    /**
     * Get the base 64 character for the value
     *
     * @param int $value The int value
     *
     * @return string
     * @psalm-api
     */
    static public function chr(int $value): string
    {
        return self::CHARSET[$value];
    }

    /**
     * Encode an int value to pseudo base 64
     *
     * @param int $value Value to encode
     * @param int $length The expected result length
     *
     * @return string The encoded value
     * @psalm-api
     */
    static public function encode(int $value, int $length): string
    {
        $encoded = str_repeat("\0", $length);

        for ($i = $length - 1; $i >= 0; --$i) {
            $encoded[$i] = self::CHARSET[$value & 63];
            $value >>= 6;
        }

        return $encoded;
    }

    /**
     * Decode pseudo base64 value to int
     *
     * @param string $encoded The encoded value
     *
     * @return int
     * @psalm-api
     */
    static public function decode(string $encoded): int
    {
        $value = 0;
        $len = strlen($encoded);

        for ($i = 0; $i < $len; ++$i) {
            $value <<= 6;
            $value += self::ord($encoded[$i]);
        }

        return $value;
    }
}
