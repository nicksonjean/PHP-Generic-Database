<?php

use
    GenericDatabase\Engine\FBirdEngine,

    GenericDatabase\Engine\FBird\FBird;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$fbird = FBirdEngine::new(
    $_ENV['FBIRD_HOST'],
    +$_ENV['FBIRD_PORT'],
    $_ENV['FBIRD_DATABASE'],
    $_ENV['FBIRD_USER'],
    $_ENV['FBIRD_PASSWORD'],
    'utf8',
    [
        FBird::ATTR_PERSISTENT => true,
        FBird::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    true
)->connect();

var_dump($fbird);
