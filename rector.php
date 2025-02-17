<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests/unit',
    ])
    ->withSkip([
        Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class,
    ])
    ->withPhpSets(php74: true);
