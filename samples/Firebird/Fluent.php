<?php

use
    GenericDatabase\Engine\FirebirdEngine,

    GenericDatabase\Engine\Firebird\Firebird;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$firebird = FirebirdEngine
    ::setHost($_ENV['FIREBIRD_HOST'])
    ::setPort(+$_ENV['FIREBIRD_PORT'])
    ::setDatabase($_ENV['FIREBIRD_DATABASE'])
    ::setUser($_ENV['FIREBIRD_USER'])
    ::setPassword($_ENV['FIREBIRD_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        Firebird::ATTR_PERSISTENT => true,
        Firebird::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ::setException(true)
    ->connect();

var_dump($firebird);
