<?php

use PhpCsFixer\Config;

return (new Config())
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PSR1' => true,
            '@PSR2' => true,
            '@Symfony' => true,
            'psr_autoloading' => true,
            // custom rules
            'align_multiline_comment' => ['comment_type' => 'phpdocs_only'], // psr-5
            'phpdoc_to_comment' => false,
            'no_superfluous_phpdoc_tags' => false,
            'array_indentation' => true,
            'array_syntax' => ['syntax' => 'short'],
            'cast_spaces' => ['space' => 'none'],
            'concat_space' => ['spacing' => 'one'],
            'compact_nullable_type_declaration' => true,
            'declare_equal_normalize' => ['space' => 'single'],
            'general_phpdoc_annotation_remove' => [
                'annotations' => [
                    'author',
                    'package',
                ],
            ],
            'increment_style' => ['style' => 'post'],
            'list_syntax' => ['syntax' => 'short'],
            'echo_tag_syntax' => ['format' => 'long'],
            'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
            'phpdoc_align' => false,
            'phpdoc_no_empty_return' => false,
            'phpdoc_order' => true, // psr-5
            'phpdoc_no_useless_inheritdoc' => false,
            'protected_to_private' => false,
            'yoda_style' => false,
            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
            'ordered_imports' => [
                'sort_algorithm' => 'alpha',
                'imports_order' => ['class', 'function', 'const'],
            ],
            'single_line_throw' => false,
            'declare_strict_types' => false,
            'blank_line_between_import_groups' => true,
            'fully_qualified_strict_types' => true,
            'no_null_property_initialization' => false,
            'nullable_type_declaration_for_default_null_value' => false,
            'operator_linebreak' => [
                'only_booleans' => true,
                'position' => 'beginning',
            ],
            'global_namespace_import' => [
                'import_classes' => true,
                'import_constants' => null,
                'import_functions' => null
            ],
            'class_definition' => [
                'space_before_parenthesis' => true,
            ],
            'phpdoc_summary' => false,
            'no_useless_concat_operator' => false,
            'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        ]
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/Slim')
            ->in(__DIR__ . '/tests')
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );
