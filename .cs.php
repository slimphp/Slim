<?php

use PhpCsFixer\Config;

return (new Config())
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setRules(
        [
            '@PER-CS2x0' => true,
            'trailing_comma_in_multiline' => [
                'after_heredoc' => true,
                'elements' => ['array_destructuring', 'arrays', 'match'],
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
