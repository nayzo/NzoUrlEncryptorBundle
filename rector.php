<?php

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__]);
    $rectorConfig->skip([__DIR__ . '/vendor', __DIR__ . '/.github']);

    $rectorConfig->phpVersion(PhpVersion::PHP_71);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->rule(AddParamTypeFromPropertyTypeRector::class);
    $rectorConfig->rule(AddParamTypeDeclarationRector::class);

    $rectorConfig->sets([
        SetList::PHP_71,
        SetList::CODE_QUALITY,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_64,
        SymfonySetList::CONFIGS,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);
};
