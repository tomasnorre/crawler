<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths(
        [
            __DIR__ . '/Classes',
            __DIR__ . '/Configuration',
            __DIR__ . '/Documentation',
            __DIR__ . '/Tests',
        ]
    )
    ->withPreparedSets(
        psr12: true,
        common: true
    )
    ->withRules([
        LineLengthFixer::class,
        NoBlankLinesAfterPhpdocFixer::class,
        NoExtraBlankLinesFixer::class,
        NoLeadingImportSlashFixer::class,
        NoUnusedImportsFixer::class,
        TernaryOperatorSpacesFixer::class,
    ])
    ->withConfiguredRule(ArraySyntaxFixer::class, ['syntax' => 'short'])
    ->withConfiguredRule(ConcatSpaceFixer::class, ['spacing' => 'one'])
    ->withConfiguredRule(BinaryOperatorSpacesFixer::class, ['default' => 'single_space'])
    ->withConfiguredRule(AlignMultilineCommentFixer::class, ['comment_type' => 'phpdocs_only'])
    ->withConfiguredRule(GeneralPhpdocAnnotationRemoveFixer::class, ['annotations' => ['author', 'since']])
    ->withConfiguredRule(OrderedImportsFixer::class, ['imports_order' => ['class', 'const', 'function']])
    ->withSkip([
        __DIR__ . '/Tests/Acceptance/Support/_generated',
        'PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer' => null,
        'PhpCsFixer\Fixer\Strict\StrictParamFixer' => null,
        PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class => null,
        'PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff.Found' => null,
        PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer::class => null,
        Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer::class => null
    ]);
