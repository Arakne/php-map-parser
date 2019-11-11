<?php

namespace Arakne\MapParser\Test;

use Imagick;

trait AssertImageTrait
{
    public function assertImages($expected, $actual, float $delta = .01)
    {
        $expected = new Imagick($expected);
        $actual = new Imagick($actual);

        $result = $expected->compareImages($actual, Imagick::METRIC_MEANABSOLUTEERROR);

        $this->assertTrue($result[1] < $delta, 'The two images are not equals : delta = '.$result[1]);
    }
}
