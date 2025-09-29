<?php

namespace Tile\Coordinate;

use Arakne\MapParser\Tile\Coordinate\LatLongBounds;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LatLongBoundsTest extends TestCase
{

    #[Test]
    public function fromString()
    {
        $bounds = LatLongBounds::fromString('1.0,2.0,3.0,4.0');
        $this->assertInstanceOf(LatLongBounds::class, $bounds);
        $this->assertSame(1.0, $bounds->west);
        $this->assertSame(2.0, $bounds->south);
        $this->assertSame(3.0, $bounds->east);
        $this->assertSame(4.0, $bounds->north);
    }

    #[Test]
    public function fromStringInvalidNumberOfElements()
    {
        $bounds = LatLongBounds::fromString('1.0,2.0,3.0');
        $this->assertNull($bounds);
    }

    #[Test]
    public function fromStringInvalidValues()
    {
        $this->assertNull(LatLongBounds::fromString('200.0,2.0,3.0,4.0')); // Invalid west
        $this->assertNull(LatLongBounds::fromString('1.0,-100.0,3.0,4.0')); // Invalid south
        $this->assertNull(LatLongBounds::fromString('1.0,2.0,200.0,4.0')); // Invalid east
        $this->assertNull(LatLongBounds::fromString('1.0,2.0,3.0,100.0')); // Invalid north
        $this->assertNull(LatLongBounds::fromString('1.0,4.0,3.0,2.0')); // south > north
        $this->assertNull(LatLongBounds::fromString('3.0,2.0,1.0,4.0')); // west > east
    }
}
