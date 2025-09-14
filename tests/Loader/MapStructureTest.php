<?php

namespace Loader;

use Arakne\MapParser\Loader\MapStructure;
use Arakne\Swf\SwfFile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class MapStructureTest extends TestCase
{
    public function test_fromSwfFile_valid()
    {
        $map = MapStructure::fromSwfFile(
            new SwfFile(__DIR__.'/../_files/10302_0709271842X.swf'),
            $key = file_get_contents(__DIR__.'/../_files/10302.key')
        );

        $this->assertSame(10302, $map->id);
        $this->assertSame(15, $map->width);
        $this->assertSame(17, $map->height);
        $this->assertSame(438, $map->background);
        $this->assertSame(17, $map->ambiance);
        $this->assertSame(129, $map->music);
        $this->assertTrue($map->outdoor);
        $this->assertSame(78, $map->capabilities);
        $this->assertSame(file_get_contents(__DIR__.'/../_files/10302.data'), $map->data);
        $this->assertSame($key, $map->key);
        $this->assertTrue($map->encrypted);
    }

    public function test_fromSwfFile_not_swf_file()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SWF file is not valid');
        MapStructure::fromSwfFile(new SwfFile(__FILE__));
    }

    public function test_fromSwfFile_file_too_big()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SWF file is not valid');
        MapStructure::fromSwfFile(new SwfFile(__DIR__.'/../_files/clips/gfx/o1.swf'));
    }

    public function test_fromSwfFile_not_map_file()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SWF file does not contain a valid map structure');
        MapStructure::fromSwfFile(new SwfFile(__DIR__.'/../_files/not_map.swf'));
    }
}
