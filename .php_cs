<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PHP74Migration' => true,
        '@PSR2' => true,
        'strict_param' => true,
        'cast_spaces' => ['space' => 'none'],
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
