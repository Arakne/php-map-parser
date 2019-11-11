<?php

namespace Arakne\MapParser\Parser;

use PHPUnit\Framework\TestCase;

/**
 * Class CellDataParserTest
 */
class CellDataParserTest extends TestCase
{
    /**
     * @var CellDataParser
     */
    private $parser;

    protected function setUp()
    {
        $this->parser = new CellDataParser();
    }

    /**
     *
     */
    public function test_parse_map()
    {
        $cells = $this->parser->parse(file_get_contents(__DIR__.'/../_files/10340.data'));

        $this->assertCount(479, $cells);
        $this->assertContainsOnly(Cell::class, $cells);
    }

    /**
     *
     */
    public function test_parse_empty_cell()
    {
        $cell = $this->parser->parse("Hhaaeaaaaa")[0];

        $this->assertTrue($cell->lineOfSight());
        $this->assertFalse($cell->ground()->active());
        $this->assertFalse($cell->layer1()->active());
        $this->assertFalse($cell->layer2()->active());
    }

    /**
     *
     */
    public function test_parse_not_empty_cell()
    {
        $cell = $this->parser->parse("GhhceaaaWt")[0];

        $this->assertFalse($cell->lineOfSight());
        $this->assertEquals(0, $cell->movement());
        $this->assertTrue($cell->active());

        $this->assertEquals(450, $cell->ground()->number());
        $this->assertFalse($cell->ground()->flip());
        $this->assertEquals(0, $cell->ground()->rotation());
        $this->assertEquals(7, $cell->ground()->level());
        $this->assertEquals(1, $cell->ground()->slope());
        $this->assertTrue($cell->ground()->active());

        $this->assertEquals(0, $cell->layer1()->number());
        $this->assertEquals(0, $cell->layer1()->rotation());
        $this->assertFalse($cell->layer1()->flip());
        $this->assertFalse($cell->layer1()->active());

        $this->assertFalse($cell->layer2()->interactive());
        $this->assertEquals(3091, $cell->layer2()->number());
        $this->assertEquals(0, $cell->layer2()->rotation());
        $this->assertFalse($cell->layer2()->flip());
        $this->assertTrue($cell->layer2()->active());
    }
}
