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

use InvalidArgumentException;
use SensitiveParameter;

use function assert;
use function ceil;
use function hex2bin;
use function str_repeat;
use function strlen;
use function substr;
use function urldecode;

/**
 * Implementation of the xor cipher in Dofus 1
 * Note: This cipher is not secure, the key can be easily retrieved.
 *
 * https://github.com/Emudofus/Dofus/blob/1.29/dofus/aks/Aks.as#L297
 */
final readonly class XorCipher
{
    public function __construct(
        /**
         * The decoded key used for encryption/decryption
         * If your key is encoded, prefer using {@see XorCipher::fromHexKey()} static constructor instead.
         */
        #[SensitiveParameter]
        private string $key,
    ) {}

    /**
     * Decrypt the value using the current key
     *
     * https://github.com/Emudofus/Dofus/blob/1.29/dofus/aks/Aks.as#L314
     *
     * @param string $value Value to decrypt. Must be a valid hexadecimal string
     * @param int|null $keyOffset Offset to use on the key. Must be the same used for encryption. By default, it is computed using the checksum of the key (see {@see Checksum::integer()}).
     *
     * @return string The decrypted value
     *
     * @throws InvalidArgumentException When an invalid string is given
     */
    public function decrypt(string $value, ?int $keyOffset = null): string
    {
        $keyOffset ??= Checksum::integer($this->key) * 2;
        $value = @hex2bin($value) or throw new InvalidArgumentException('Invalid encrypted value');
        $len = strlen($value);

        $keyLen = strlen($this->key);
        $keyOffset = $keyOffset % $keyLen;
        $key = substr($this->key, $keyOffset) . substr($this->key, 0, $keyOffset);
        $key = substr(str_repeat($key, (int) ceil($len / $keyLen)), 0, $len);

        assert(strlen($key) === $len);

        return urldecode($key ^ $value);
    }

    /**
     * Parse a hexadecimal encoded key and return a new XorCipher instance
     *
     * @param string $key The encoded key
     * @return self
     */
    public static function fromHexKey(#[SensitiveParameter] string $key): self
    {
        $key = hex2bin($key) or throw new InvalidArgumentException('Invalid hex key');

        return new self(urldecode($key));
    }
}
