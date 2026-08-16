<?php

declare(strict_types=1);

$header = <<<'HEADER'
    This file is part of the kreativsoehne/sulu-email-obfuscator-bundle package.

    (c) Kreativ&Söhne GmbH

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    HEADER;

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_definition' => ['multi_line_extends_each_single_line' => true],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'header_comment' => ['header' => $header, 'separate' => 'both'],
        'linebreak_after_opening_tag' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'multiline_whitespace_before_semicolons' => true,
        'native_constant_invocation' => true,
        'native_function_casing' => true,
        'native_function_invocation' => ['include' => ['@internal']],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'php_unit_strict' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_to_comment' => ['ignored_tags' => ['todo', 'var']],
        'semicolon_after_instruction' => true,
        'single_line_throw' => false,
        'strict_comparison' => true,
        'strict_param' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
    ])
    ->setFinder($finder);
