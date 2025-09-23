<?php

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

    public function __debugInfo(): ?array
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
