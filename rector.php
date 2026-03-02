<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        LaravelSetList::LARAVEL_110,
    ])
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    )
    ->withSkip([
        // Protect interface method signatures from type additions — these are implemented externally
        __DIR__ . '/src/Interfaces',
    ]);
