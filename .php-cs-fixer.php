<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
;

return new Config()
    ->setRules([
        '@PER' => true,
    ])
    ->setFinder($finder)
;
