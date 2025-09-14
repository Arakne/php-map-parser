<?php

namespace Sprite;

use Arakne\MapParser\Sprite\Sprite;
use Arakne\MapParser\Sprite\SpriteState;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use Arakne\MapParser\Test\AssertImageTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function glob;
use function unlink;

class SwfSpriteRepositoryTest extends TestCase
{
    use AssertImageTrait;

    private SwfSpriteRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new SwfSpriteRepository(glob(__DIR__ . '/../_files/clips/gfx/o*.swf'));
    }

    #[Test]
    public function get_success()
    {
        $sprite = $this->repository->get(7019);

        $this->assertSame(7019, $sprite->id);
        $this->assertTrue($sprite->valid);
        $this->assertSame(SpriteState::Valid, $sprite->state);
        $this->assertSame(-29, $sprite->offsetX);
        $this->assertSame(-90, $sprite->offsetY);
        $this->assertSame(58, $sprite->width);
        $this->assertSame(106, $sprite->height);

        file_put_contents(__DIR__ . '/Fixtures/sprite.png', $sprite->pngData);
        $this->assertImages(
            __DIR__ . '/Fixtures/7019.png',
            __DIR__ . '/Fixtures/sprite.png'
        );
        unlink(__DIR__ . '/Fixtures/sprite.png');
    }

    #[Test]
    public function get_not_found()
    {
        $sprite = $this->repository->get(555555);

        $this->assertSame(555555, $sprite->id);
        $this->assertFalse($sprite->valid);
        $this->assertSame(SpriteState::Missing, $sprite->state);
        $this->assertSame(0, $sprite->offsetX);
        $this->assertSame(0, $sprite->offsetY);
        $this->assertSame(0, $sprite->width);
        $this->assertSame(0, $sprite->height);
        $this->assertSame(Sprite::EMPTY_PNG, $sprite->pngData);
    }

    #[Test]
    public function get_empty()
    {
        $sprite = $this->repository->get(3669);

        $this->assertSame(3669, $sprite->id);
        $this->assertFalse($sprite->valid);
        $this->assertSame(SpriteState::Empty, $sprite->state);
        $this->assertSame(0, $sprite->offsetX);
        $this->assertSame(0, $sprite->offsetY);
        $this->assertSame(0, $sprite->width);
        $this->assertSame(0, $sprite->height);
        $this->assertSame(Sprite::EMPTY_PNG, $sprite->pngData);
    }
}
