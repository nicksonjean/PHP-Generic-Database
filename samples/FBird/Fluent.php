<?php

use
    GenericDatabase\Engine\FBirdEngine,

    GenericDatabase\Engine\FBird\FBird;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$fbird = FBirdEngine
    ::setHost($_ENV['FBIRD_HOST'])
    ::setPort(+$_ENV['FBIRD_PORT'])
    ::setDatabase($_ENV['FBIRD_DATABASE'])
    ::setUser($_ENV['FBIRD_USER'])
    ::setPassword($_ENV['FBIRD_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        FBird::ATTR_PERSISTENT => true,
        FBird::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ::setException(true)
    ->connect();

var_dump($fbird);
