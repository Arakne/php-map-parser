<?php

namespace Sprite;

use Arakne\MapParser\Sprite\Sprite;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use Arakne\Swf\SwfFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function imagepng;
use function unlink;

class SpriteTest extends TestCase
{
    use AssertImageTrait;

    private SwfSpriteRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new SwfSpriteRepository([__DIR__ . '/../_files/clips/gfx/g1.swf']);
    }

    #[Test]
    public function rotate1()
    {
        $sprite = $this->repository->get(139);
        $rotated = $sprite->rotate(1);

        imagepng($rotated->gd(), $path = __DIR__ . '/Fixtures/actual_139_r1.png');
        $this->assertImages($path, __DIR__ . '/Fixtures/139_r1.png');
        unlink($path);

        $this->assertSame(139, $rotated->id);
        $this->assertTrue($rotated->valid);
        $this->assertSame(-31.0, $rotated->offsetX);
        $this->assertSame(-16.0, $rotated->offsetY);
        $this->assertSame(61.0, $rotated->width);
        $this->assertSame(33.0, $rotated->height);
    }

    #[Test]
    public function rotate2()
    {
        $sprite = $this->repository->get(139);
        $rotated = $sprite->rotate(2);

        imagepng($rotated->gd(), $path = __DIR__ . '/Fixtures/139_r2.png');
        $this->assertImages($path, __DIR__ . '/Fixtures/139_r2.png');
        unlink($path);

        $this->assertSame(139, $rotated->id);
        $this->assertTrue($rotated->valid);
        $this->assertEqualsWithDelta(-31.95, $rotated->offsetX, 0.00001);
        $this->assertEquals(-16.1, $rotated->offsetY, 0.00001);
        $this->assertSame(62.7, $rotated->width, 0.00001);
        $this->assertSame(31.35, $rotated->height);
    }

    #[Test]
    public function rotate3()
    {
        $sprite = $this->repository->get(139);
        $rotated = $sprite->rotate(3);

        imagepng($rotated->gd(), $path = __DIR__ . '/Fixtures/139_r3.png');
        $this->assertImages($path, __DIR__ . '/Fixtures/139_r3.png');
        unlink($path);

        $this->assertSame(139, $rotated->id);
        $this->assertTrue($rotated->valid);
        $this->assertSame(-30.0, $rotated->offsetX);
        $this->assertSame(-17.0, $rotated->offsetY);
        $this->assertSame(61.0, $rotated->width);
        $this->assertSame(33.0, $rotated->height);
    }
}
