<?php

namespace Util;

use Arakne\MapParser\Util\Bounds;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BoundsTest extends TestCase
{
    #[Test]
    public function toActualMapBounds()
    {
        $bounds = new Bounds(
            xMin: -2,
            xMax: 3,
            yMin: -4,
            yMax: 1,
        );

        $actual = $bounds->toActualMapBound();

        $this->assertNotEquals($actual, $bounds);
        $this->assertSame(-30, $actual->xMin);
        $this->assertSame(59, $actual->xMax);
        $this->assertSame(-60, $actual->yMin);
        $this->assertSame(29, $actual->yMax);
    }
}
