<?php

namespace Loader;

use Arakne\MapParser\Loader\MapKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function print_r;

class MapKeyTest extends TestCase
{
    #[Test]
    public function functionalDecrypt()
    {
        $key = MapKey::fromFile(__DIR__ . '/../_files/10302.key');
        $data = file_get_contents(__DIR__ . '/../_files/10302.data');

        $this->assertSame(
            file_get_contents(__DIR__ . '/../_files/10302.data.decoded'),
            $key->decrypt($data),
        );
    }

    #[Test]
    public function dontLeakKeyOnDebug()
    {
        $key = new MapKey('0123456789abcdef');

        $this->assertStringNotContainsString($key->key, print_r($key, true));
    }
}
