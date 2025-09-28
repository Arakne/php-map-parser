<?php

namespace Renderer;

use Arakne\MapParser\Loader\Map;
use Arakne\MapParser\Renderer\MapRenderer;
use Arakne\MapParser\Renderer\MapScale;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MapScaleTest extends TestCase
{
    #[Test]
    public function forSmallMap()
    {
        $map = new Map(0, 10, 12, 0, []);
        $scale = MapScale::for($map);

        $this->assertSame(1.0, $scale->scale);
        $this->assertSame(132, $scale->offsetX);
        $this->assertSame(67, $scale->offsetY);
        $this->assertSame(477, $scale->scaledWidth);
        $this->assertSame(297, $scale->scaledHeight);
    }

    #[Test]
    public function forBigMap()
    {
        $map = new Map(0, 35, 51, 0, []);
        $scale = MapScale::for($map);

        $this->assertEqualsWithDelta(0.41, $scale->scale, 0.005);
        $this->assertSame(0, $scale->offsetX);
        $this->assertSame(-61, $scale->offsetY);
        $this->assertSame(742, $scale->scaledWidth);
        $this->assertSame(555, $scale->scaledHeight);
    }

    #[Test]
    public function forDefaultDimensionMap()
    {
        $map = new Map(0, 15, 17, 0, []);
        $scale = MapScale::for($map);

        $this->assertSame(1.0, $scale->scale);
        $this->assertSame(0, $scale->offsetX);
        $this->assertSame(0, $scale->offsetY);
        $this->assertSame(MapRenderer::DISPLAY_WIDTH, $scale->scaledWidth);
        $this->assertSame(MapRenderer::DISPLAY_HEIGHT, $scale->scaledHeight);
    }
}
