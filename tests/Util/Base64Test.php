<?php


namespace Arakne\MapParser\Util;

use PHPUnit\Framework\TestCase;

/**
 * Class Base64Test
 */
class Base64Test extends TestCase
{
    public function test_ordSuccess()
    {
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';

        for ($i = 0; $i < strlen($charset); ++$i) {
            $this->assertEquals($i, Base64::ord($charset[$i]));
        }
    }

    public function test_ordInvalidChar()
    {
        $this->expectException(\InvalidArgumentException::class);

        Base64::ord('#');
    }

    public function test_encodeSingleChar()
    {
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';

        for ($i = 0; $i < strlen($charset); ++$i) {
            $this->assertEquals($charset[$i], Base64::encode($i, 1));
        }
    }

    public function test_encodeWithTwoChars()
    {
        $this->assertEquals('cr', Base64::encode(145, 2));
    }


    public function test_encodeWithTooSmallNumberWillKeepLength()
    {
        $this->assertEquals("aac", Base64::encode(2, 3));
    }


    public function test_chr()
    {
        $this->assertEquals('a', Base64::chr(0));
        $this->assertEquals('_', Base64::chr(63));
        $this->assertEquals('c', Base64::chr(2));
    }


    public function test_decodeWithOneChar()
    {
        $this->assertEquals(0, Base64::decode('a'));
        $this->assertEquals(2, Base64::decode('c'));
        $this->assertEquals(63, Base64::decode('_'));
    }


    public function test_decode()
    {
        $this->assertEquals(458, Base64::decode('hk'));
    }

    public function test_decodeEncodeTwoChars()
    {
        $this->assertEquals(741, Base64::decode(Base64::encode(741, 2)));
        $this->assertEquals(951, Base64::decode(Base64::encode(951, 2)));
        $this->assertEquals(325, Base64::decode(Base64::encode(325, 2)));
        $this->assertEquals(769, Base64::decode(Base64::encode(769, 2)));
    }
}
