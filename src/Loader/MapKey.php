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

namespace Arakne\MapParser\Loader;

use Arakne\MapParser\Util\XorCipher;
use RuntimeException;
use SensitiveParameter;

use function file_get_contents;
use function is_string;
use function trim;

/**
 * Store the key used to encrypt/decrypt map data.
 * Add it to attachments on second parameter of {@see MapLoader::load()} to enable decryption.
 */
final readonly class MapKey
{
    public function __construct(
        #[SensitiveParameter]
        public string $key,
    ) {}

    /**
     * Decrypt the given map data using the stored key.
     *
     * @param string $mapData
     * @return string
     */
    public function decrypt(string $mapData): string
    {
        return XorCipher::fromHexKey($this->key)->decrypt($mapData);
    }

    public function __debugInfo(): array
    {
        // Disable debug info to avoid leaking the key
        return [];
    }

    /**
     * Read the key from a file.
     *
     * @param string $file
     * @return self
     */
    public static function fromFile(string $file): self
    {
        $key = file_get_contents($file);

        if (!is_string($key)) {
            throw new RuntimeException('Failed to read the key file');
        }

        return new self(trim($key));
    }
}
