<?php

use
    GenericDatabase\Engine\FBirdEngine,

    GenericDatabase\Engine\FBird\FBird;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$fbird = FBirdEngine::new([
    'host' => $_ENV['FBIRD_HOST'],
    'port' => +$_ENV['FBIRD_PORT'],
    'database' => $_ENV['FBIRD_DATABASE'],
    'user' => $_ENV['FBIRD_USER'],
    'password' => $_ENV['FBIRD_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        FBird::ATTR_PERSISTENT => true,
        FBird::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    'exception' => true
])->connect();

var_dump($fbird);
