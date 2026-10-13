<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__])
    ->withSkip([__DIR__ . '/vendor', __DIR__ . '/.github'])
    ->withPhpSets(php82: true)
    ->withPreparedSets(codeQuality: true, typeDeclarations: true)
    ->withImportNames(importShortClasses: false);
