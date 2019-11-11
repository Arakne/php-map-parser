<?php


namespace Arakne\MapParser\Renderer;

use Arakne\MapParser\Parser\Cell;
use Arakne\MapParser\Parser\CellDataParser;
use Arakne\MapParser\Parser\LayerObjectInterface;
use Swf\Cli\Jar;
use Swf\Processor\BulkLoader;
use Swf\SwfLoader;

/**
 * Class MapRenderer
 */
class MapRenderer
{
    const DISPLAY_WIDTH = 742;
    const DISPLAY_HEIGHT = 432;

    const CELL_WIDTH = 53;
    const CELL_HEIGHT = 27;
    const CELL_HALF_WIDTH = 2.650000E+001;
    const CELL_HALF_HEIGHT = 1.350000E+001;
    const LEVEL_HEIGHT = 20;

    /**
     * @var BulkLoader
     */
    private $groundLoader;

    /**
     * @var BulkLoader
     */
    private $objectLoader;

    private $cachedir;

    public function __construct()
    {
        $this->cachedir = __DIR__.'/../../cache/map_renderer_1';

        if (!is_dir($this->cachedir.'/ground')) {
            mkdir($this->cachedir.'/ground', 0777, true);
        }

        if (!is_dir($this->cachedir.'/object')) {
            mkdir($this->cachedir.'/object', 0777, true);
        }

        $jar = new Jar('/home/vincent/.local/app/ffdec_11.2.0_nightly1722/ffdec.jar');
        $loader = new SwfLoader($jar);

        if (file_exists($this->cachedir.'/ground_loader.cache')) {
            $this->groundLoader = unserialize(file_get_contents($this->cachedir.'/ground_loader.cache'));
        } else {
            $this->groundLoader = $loader->bulk(glob('/home/vincent/Documents/Dofus/Dofus/clips/gfx/g*.swf'))->setResultDirectory($this->cachedir.'/ground');
        }

        if (file_exists($this->cachedir.'/object_loader.cache')) {
            $this->objectLoader = unserialize(file_get_contents($this->cachedir.'/object_loader.cache'));
        } else {
            $this->objectLoader = $loader->bulk(glob('/home/vincent/Documents/Dofus/Dofus/clips/gfx/o*.swf'))->setResultDirectory($this->cachedir.'/object');
        }
    }

    public function render(string $mapData, int $width, int $height)
    {
        $img = imagecreatetruecolor(self::DISPLAY_WIDTH, self::DISPLAY_HEIGHT);

        $cells = (new CellDataParser())->parse($mapData);

        foreach ($cells as $cell) {
            if ($cell->ground()->number()) {
                $this->groundLoader->add($cell->ground()->number());
            }

            if ($cell->layer1()->number()) {
                $this->objectLoader->add($cell->layer1()->number());
            }

            if ($cell->layer2()->number()) {
                $this->objectLoader->add($cell->layer2()->number());
            }
        }

        $this->groundLoader->load();
        $this->objectLoader->load();

        $_loc14 = $width - 1;
        $_loc9 = -1;
        $_loc10 = 0;
        $_loc11 = 0;

        /** @var CellRenderer[] $cellRenderers */
        $cellRenderers = [];

        foreach ($cells as $cell) {
            if ($_loc9 == $_loc14) {
                $_loc9 = 0;
                ++$_loc10;

                if ($_loc11 == 0){
                    $_loc11 = self::CELL_HALF_WIDTH;
                    --$_loc14;
                }else{
                    $_loc11 = 0;
                    ++$_loc14;
                }
            } else {
                ++$_loc9;
            }

            $x = (int)($_loc9 * self::CELL_WIDTH + $_loc11);
            $y = (int)($_loc10 * self::CELL_HALF_HEIGHT - self::LEVEL_HEIGHT * ($cell->ground()->level() - 7));

            $cellRenderers[] = new CellRenderer($this->groundLoader, $this->objectLoader, $cell, $x, $y);
        }

        foreach ($cellRenderers as $cellRenderer) {
            $cellRenderer->ground($img);
        }

        foreach ($cellRenderers as $cellRenderer) {
            $cellRenderer->layer1($img);
        }

        foreach ($cellRenderers as $cellRenderer) {
            $cellRenderer->layer2($img);
        }

        return $img;
    }

    public function __destruct()
    {
        file_put_contents($this->cachedir.'/ground_loader.cache', serialize($this->groundLoader));
        file_put_contents($this->cachedir.'/object_loader.cache', serialize($this->objectLoader));
    }
}

class CellRenderer
{
    /**
     * @var BulkLoader
     */
    private $groundLoader;

    /**
     * @var BulkLoader
     */
    private $objectLoader;

    /**
     * @var Cell
     */
    private $cell;

    /**
     * @var int
     */
    private $x;

    /**
     * @var int
     */
    private $y;


    /**
     * CellRenderer constructor.
     *
     * @param BulkLoader $groundLoader
     * @param BulkLoader $objectLoader
     * @param Cell $cell
     * @param int $x
     * @param int $y
     */
    public function __construct(BulkLoader $groundLoader, BulkLoader $objectLoader, Cell $cell, int $x, int $y)
    {
        $this->groundLoader = $groundLoader;
        $this->objectLoader = $objectLoader;

        $this->cell = $cell;
        $this->x = $x;
        $this->y = $y;
    }

    public function ground($img)
    {
        $this->renderLayer($this->cell->ground(), $this->groundLoader, $this->x, $this->y, $img);
    }

    public function layer1($img)
    {
        $this->renderLayer($this->cell->layer1(), $this->objectLoader, $this->x, $this->y, $img);
    }

    public function layer2($img)
    {
        $this->renderLayer($this->cell->layer2(), $this->objectLoader, $this->x, $this->y, $img);
    }

    private function renderLayer(LayerObjectInterface $layer, BulkLoader $spriteLoader, int $x, int $y, $out)
    {
        $sprite = $spriteLoader->get($layer->number());

        if (!$sprite) {
            return;
        }

        $img = imagecreatefrompng($sprite->frame());

        $width = imagesx($img);
        $height = imagesy($img);

        if (!$sprite->bounds()) {
            return;
            //throw new \RuntimeException('Invalid bounds '.$sprite->frame());
        }

        $y = $y + $sprite->bounds()->Yoffset();

        if ($sprite->bounds()->width() == 0) {
            file_put_contents(__DIR__.'/../../log', $sprite->frame().PHP_EOL, FILE_APPEND);
        }

        if ($layer->flip()) {
            imageflip($img, IMG_FLIP_HORIZONTAL);

            $x = $x - $sprite->bounds()->Xoffset() - $width;
        } else {
            $x = $x + $sprite->bounds()->Xoffset();
        }

        imagecopy($out, $img, $x, $y, 0, 0, $width, $height);

        imagedestroy($img);
    }
}
