<?php

namespace Arakne\MapParser\Test;

use Imagick;

use function is_array;

trait AssertImageTrait
{
    public function assertImages($expected, $actual, float $delta = .01)
    {
        $expected = new Imagick($expected);

        if (!is_array($actual)) {
            $actual = [$actual];
        }

        $result = 1.0;

        foreach ($actual as $path) {
            $img = new Imagick($path);
            $curRes = $expected->compareImages($img, Imagick::METRIC_MEANABSOLUTEERROR);

            if ($curRes[1] < $result) {
                $result = $curRes[1];
            }
        }

        $this->assertTrue($result < $delta, 'The two images are not equals : delta = '.$result);
    }
}
