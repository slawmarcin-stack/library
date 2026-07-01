<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        SetList::PSR_12,
        SetList::COMMON,
        SetList::CLEAN_CODE,
    ])
    ->withConfiguredRule(DeclareStrictTypesFixer::class, [
        'preserve_existing_declaration' => true,
    ]);
