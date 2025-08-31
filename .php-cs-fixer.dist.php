<?php

declare(strict_types=1);

$rules = [
    '@PSR12'                      => true,
    'declare_strict_types'        => true,
    'linebreak_after_opening_tag' => true,
    'mb_str_functions'            => true,
    'modernize_types_casting'     => true,
    'no_php4_constructor'         => true,
//    'no_short_echo_tag'           => true,
    'no_useless_else'             => true,
    'no_useless_return'           => true,
    'no_unused_imports'           => true,
    'ordered_class_elements'      => true,
    'ordered_imports'             => true,
    'semicolon_after_instruction' => true,
];

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
