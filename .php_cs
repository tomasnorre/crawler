<?php
PHP_SAPI === 'cli' or die();

/**
 * PHP Coding Standards Fixer (PHP CS Fixer) Configuration
 *
 * For in-depth documentation and an installation guide, see:
 *     https://github.com/FriendsOfPHP/PHP-CS-Fixer
 *
 * Usage:
 *     $ php-cs-fixer fix
 */

// Configure the extension root directory to be analyzed recursively
$finder = PhpCsFixer\Finder::create()->in(__DIR__);

// Define additional rules on top of the default PSR-2 configuration
return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,

        // (associative) arrays
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],

        // general code formatting
        'no_extra_consecutive_blank_lines' => true,

        // use statements
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
    ])
    ->setFinder($finder);
