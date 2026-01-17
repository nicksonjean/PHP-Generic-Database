<?php

declare(strict_types=1);

// Configurações de performance para evitar travamentos
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');
ini_set('max_input_time', '-1');
set_time_limit(0);

// Configurações adicionais para melhorar o desempenho
ini_set('realpath_cache_size', '4096K');
ini_set('realpath_cache_ttl', '600');

use Doctum\Doctum;
use Symfony\Component\Finder\Finder;
use Doctum\RemoteRepository\GitHubRemoteRepository;

$dir = __DIR__ . '/src';
$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir);

return new Doctum($iterator, [
    'title' => 'PHP Generic Database Documentation',
    'build_dir' => __DIR__ . '/docs',
    'cache_dir' => __DIR__ . '/cache',
    'default_opened_level' => 2,
    'remote_repository' => new GitHubRemoteRepository('nicksonjean/PHP-Generic-Database', dirname($dir)),
    'base_url' => 'https://github.com/nicksonjean/PHP-Generic-Database',
    'favicon' => 'https://raw.githubusercontent.com/nicksonjean/PHP-Generic-Database/refs/heads/main/favicon.ico',
    'language' => 'en',
    'footer_link' => [
        'href' => 'https://github.com/nicksonjean/PHP-Generic-Database',
        'rel' => 'noreferrer noopener',
        'target' => '_blank',
        'before_text' => 'Learn more about the',
        'link_text' => 'PHP Generic Database',
        'after_text' => 'if you like!',
    ],
]);
