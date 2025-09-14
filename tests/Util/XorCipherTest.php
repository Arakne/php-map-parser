<?php

namespace Util;

use Arakne\MapParser\Util\XorCipher;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class XorCipherTest extends TestCase
{
    public function test_decrypt()
    {
        $cipher = new XorCipher('my key');

        $this->assertEquals('Hello World !', $cipher->decrypt('251C4C070A593A16520701594C', 0));
        $this->assertEquals('Hello John !', $cipher->decrypt('251C4C070A59271648054558', 0));
        $this->assertEquals('éà', $cipher->decrypt('483A134E2440483A134E2449', 0));
        $this->assertEquals('Hello John !', $cipher->decrypt('230015011600210A11035901', 3));

        $this->assertNotEquals('Hello World !', new XorCipher('other key')->decrypt('251C4C070A593A16520701594C', 0));
    }

    public function test_decrypt_long_data()
    {
        $cipher = XorCipher::fromHexKey(file_get_contents(__DIR__.'/../_files/10302.key'));

        $this->assertSame(file_get_contents(__DIR__.'/../_files/10302.data.decoded'), $cipher->decrypt(file_get_contents(__DIR__.'/../_files/10302.data')));
    }

    /**
     * @testWith ["invalid"]
     *           ["####"]
     */
    public function test_decrypt_invalid_values(string $value)
    {
        $this->expectException(InvalidArgumentException::class);

        $cipher = new XorCipher("my key");
        $cipher->decrypt($value, 0);
    }
}
