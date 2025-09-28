<?php

namespace Renderer\Layer;

use Arakne\MapParser\Renderer\Layer\BackgroundLayerRenderer;
use Arakne\MapParser\Renderer\Layer\GridLayerRenderer;
use Arakne\MapParser\Renderer\Layer\LayerObjectRenderer;
use Arakne\MapParser\Renderer\Layer\LayerRendersBuilder;
use Arakne\MapParser\Sprite\SwfSpriteRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function glob;

class LayerRendersBuilderTest extends TestCase
{
    private SwfSpriteRepository $grounds;
    private SwfSpriteRepository $objects;

    protected function setUp(): void
    {
        $this->grounds = new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/g*.swf'));
        $this->objects = new SwfSpriteRepository(glob(__DIR__ . '/../../_files/clips/gfx/o*.swf'));
    }

    #[Test]
    public function defaultLayers()
    {
        $layers = new LayerRendersBuilder($this->grounds, $this->objects)->build();

        $this->assertCount(4, $layers);
        $this->assertInstanceOf(BackgroundLayerRenderer::class, $layers[0]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[1]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[2]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[3]);
    }

    #[Test]
    public function grid()
    {
        $builder = new LayerRendersBuilder($this->grounds, $this->objects)->enableGrid();

        $layers = $builder->build();
        $this->assertCount(5, $layers);
        $this->assertInstanceOf(BackgroundLayerRenderer::class, $layers[0]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[1]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[2]);
        $this->assertInstanceOf(GridLayerRenderer::class, $layers[3]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[4]);

        $layers = $builder->disableGrid()->build();
        $this->assertCount(4, $layers);
        $this->assertInstanceOf(BackgroundLayerRenderer::class, $layers[0]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[1]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[2]);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[3]);
    }

    #[Test]
    public function disableAndEnableBackground()
    {
        $builder = new LayerRendersBuilder($this->grounds, $this->objects)->disableBackground();

        $layers = $builder->build();
        $this->assertCount(3, $layers);
        $this->assertInstanceOf(LayerObjectRenderer::class, $layers[0]);

        $layers = $builder->enableBackground()->build();
        $this->assertCount(4, $layers);
        $this->assertInstanceOf(BackgroundLayerRenderer::class, $layers[0]);
    }

    #[Test]
    public function disableAndEnableGround()
    {
        $builder = new LayerRendersBuilder($this->grounds, $this->objects)->disableGround();

        $layers = $builder->build();
        $this->assertCount(3, $layers);

        $layers = $builder->enableGround()->build();
        $this->assertCount(4, $layers);
    }

    #[Test]
    public function disableAndEnableObject1()
    {
        $builder = new LayerRendersBuilder($this->grounds, $this->objects)->disableObject1();

        $layers = $builder->build();
        $this->assertCount(3, $layers);

        $layers = $builder->enableObject1()->build();
        $this->assertCount(4, $layers);
    }

    #[Test]
    public function disableAndEnableObject2()
    {
        $builder = new LayerRendersBuilder($this->grounds, $this->objects)->disableObject2();

        $layers = $builder->build();
        $this->assertCount(3, $layers);

        $layers = $builder->enableObject2()->build();
        $this->assertCount(4, $layers);
    }
}
