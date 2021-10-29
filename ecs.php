<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Metrics\CyclomaticComplexitySniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $header = <<<HEADER
(c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>

This file is part of the TYPO3 Crawler Extension.

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.

The TYPO3 project - inspiring people to share!
HEADER;

    $parameters = $containerConfigurator->parameters();

    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::COMMON);

    $parameters->set(Option::SKIP,
        [
            __DIR__ . '/Tests/Acceptance/Support/_generated',
            'PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer' => null,
            'PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer' => null,
            'PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer' => null,
            'PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff.FoundInWhileCondition' => null,
            PhpUnitStrictFixer::class => null,
            PhpUnitTestAnnotationFixer::class => null,
            'SlevomatCodingStandard\Sniffs\Classes\TraitUseSpacingSniff.IncorrectLinesCountAfterLastUse' => null,
            'Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer' => null,
            'Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer' => null,
            UnaryOperatorSpacesFixer::class => null,
            StandaloneLineInMultilineArrayFixer::class => null,
            PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class => null,
        ]
    );

    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/Classes',
            __DIR__ . '/Configuration',
            __DIR__ . '/Tests',
        ]
    );

    $services = $containerConfigurator->services();

    $services->set(HeaderCommentFixer::class)
        ->call('configure', [[
            'comment_type' => 'comment',
            'header' => $header,
            'separate' => 'both',
        ]]);

    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [['syntax' => 'short']]);

    $services->set(ConcatSpaceFixer::class)
        ->call('configure', [['spacing' => 'one']]);

    $services->set(BinaryOperatorSpacesFixer::class)
        ->call('configure', [['default' => 'single_space']]);

    $services->set(NoExtraBlankLinesFixer::class);

    $services->set(TernaryOperatorSpacesFixer::class);

    $services->set(NoBlankLinesAfterPhpdocFixer::class);

    $services->set(AlignMultilineCommentFixer::class)
        ->call('configure', [['comment_type' => 'phpdocs_only']]);

    $services->set(GeneralPhpdocAnnotationRemoveFixer::class)
        ->call('configure', [['annotations' => ['author', 'since']]]);

    $services->set(NoLeadingImportSlashFixer::class);

    $services->set(NoUnusedImportsFixer::class);

    $services->set(OrderedImportsFixer::class)
        ->call('configure', [['imports_order' => ['class', 'const', 'function']]]);

    $services->set(CyclomaticComplexitySniff::class)
        ->property('complexity', 20)
        ->property('absoluteComplexity', 20);
};
