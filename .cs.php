<?php

use PhpCsFixer\Config;

return (new Config())
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PSR1' => true,
            '@PSR2' => true,
            'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
            'array_indentation' => true,
            'array_syntax' => ['syntax' => 'short'],
            'blank_line_between_import_groups' => true,
            'braces_position' => [
                'allow_single_line_empty_anonymous_classes' => true,
            ],
            'cast_spaces' => ['space' => 'none'],
            'class_definition' => [
                'space_before_parenthesis' => true,
            ],
            'compact_nullable_type_declaration' => true,
            'concat_space' => ['spacing' => 'one'],
            'declare_equal_normalize' => ['space' => 'none'],
            'declare_strict_types' => false,
            'echo_tag_syntax' => ['format' => 'long'],
            'fully_qualified_strict_types' => true,
            'function_declaration' => [
                'closure_fn_spacing' => 'none',
            ],
            'general_phpdoc_annotation_remove' => [
                'annotations' => [
                    'author',
                    'package',
                ],
            ],
            'global_namespace_import' => [
                'import_classes' => true,
                'import_constants' => null,
                'import_functions' => null,
            ],
            'increment_style' => ['style' => 'post'],
            'list_syntax' => ['syntax' => 'short'],
            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
            'no_null_property_initialization' => false,
            'no_superfluous_phpdoc_tags' => false,
            'no_unused_imports' => true,
            'nullable_type_declaration_for_default_null_value' => false,
            'operator_linebreak' => [
                'only_booleans' => true,
                'position' => 'beginning',
            ],
            'ordered_imports' => [
                'sort_algorithm' => 'alpha',
                'imports_order' => ['class', 'function', 'const'],
            ],
            'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
            'phpdoc_align' => false,
            'phpdoc_no_empty_return' => false,
            'phpdoc_no_useless_inheritdoc' => false,
            'phpdoc_order' => true,
            'phpdoc_to_comment' => false,
            'protected_to_private' => false,
            'psr_autoloading' => true,
            'single_line_throw' => false,
            'trailing_comma_in_multiline' => [
                'after_heredoc' => true,
                'elements' => ['array_destructuring', 'arrays', 'match'],
            ],
            'yoda_style' => [
                'equal' => false,
                'identical' => false,
                'less_and_greater' => false,
            ],
        ],
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/Slim')
            ->in(__DIR__ . '/tests')
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true),
    );
