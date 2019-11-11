<?php

namespace Arakne\MapParser;

use Arakne\MapParser\Renderer\MapRenderer;
use Psr\Cache\CacheItemPoolInterface;
use Psr6NullCache\Adapter\NullCacheItemPool;
use Swf\Processor\BulkLoader;
use Swf\SwfLoader;

/**
 * Service locator for php dofus map parser
 */
class DofusMapParser
{
    /**
     * @var SwfLoader
     */
    private $swfLoader;

    /**
     * @var string
     */
    private $dofusClipsDirectory;

    /**
     * @var string
     */
    private $swfExportDirectory;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;


    /**
     * DofusMapParser constructor.
     *
     * @param string $dofusClipsDirectory
     */
    public function __construct(string $dofusClipsDirectory)
    {
        $this->dofusClipsDirectory = $dofusClipsDirectory;
    }

    /**
     * @return MapRenderer
     */
    public function renderer(): MapRenderer
    {
        return new MapRenderer(
            $this->bulkLoader(glob($this->dofusClipsDirectory . DIRECTORY_SEPARATOR . 'gfx' . DIRECTORY_SEPARATOR . 'g*.swf')),
            $this->bulkLoader(glob($this->dofusClipsDirectory . DIRECTORY_SEPARATOR . 'gfx' . DIRECTORY_SEPARATOR . 'o*.swf'))
        );
    }

    /**
     * @param string $swfExportDirectory
     *
     * @return $this
     */
    public function setSwfExportDirectory(string $swfExportDirectory): DofusMapParser
    {
        $this->swfExportDirectory = $swfExportDirectory;

        return $this;
    }

    /**
     * @param CacheItemPoolInterface $cache
     *
     * @return $this
     */
    public function setCache(CacheItemPoolInterface $cache): DofusMapParser
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param SwfLoader $swfLoader
     *
     * @return $this
     */
    public function setSwfLoader(SwfLoader $swfLoader): DofusMapParser
    {
        $this->swfLoader = $swfLoader;

        return $this;
    }

    public function swfLoader(): SwfLoader
    {
        if ($this->swfLoader) {
            return $this->swfLoader;
        }

        return $this->swfLoader = new SwfLoader();
    }

    public function cache(): CacheItemPoolInterface
    {
        if ($this->cache) {
            return $this->cache;
        }

        return $this->cache = new NullCacheItemPool();
    }

    private function bulkLoader(array $files): BulkLoader
    {
        $cacheKey = md5(implode(',', $files));

        $item = $this->cache()->getItem("bulkLoader.$cacheKey");

        if (!$item->isHit()) {
            $loader = $this->swfLoader()->bulk($files);

            $item->set($loader);
        } else {
            $loader = $item->get();
        }

        if ($this->swfExportDirectory) {
            $loader->setResultDirectory($this->swfExportDirectory . DIRECTORY_SEPARATOR . $cacheKey);
        }

        // Always refresh the cache : the loader state may change during runtime
        $this->cache()->saveDeferred($item);

        return $loader;
    }
}
