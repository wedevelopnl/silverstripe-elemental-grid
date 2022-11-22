<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
;

$config = new PhpCsFixer\Config();
$config
    ->setRules([
        '@PHP81Migration' => true,
        '@PSR12' => true,
        'array_push' => true,
        'no_unused_imports' => true,
        // TODO: Add PHPStan and enable declare_strict_Types
        'declare_strict_types' => false,
        'strict_comparison' => true,
        'strict_param' => true,
        'cast_spaces' => ['space' => 'none'],
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;

return $config;
