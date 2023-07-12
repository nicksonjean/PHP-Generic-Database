<?php

use
    GenericDatabase\Engine\OCIEngine,

    GenericDatabase\Engine\OCI\OCI;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$oci = OCIEngine::new([
    'host' => $_ENV['OCI_HOST'],
    'port' => +$_ENV['OCI_PORT'],
    'database' => $_ENV['OCI_DATABASE'],
    'user' => $_ENV['OCI_USER'],
    'password' => $_ENV['OCI_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        OCI::ATTR_PERSISTENT => true,
        OCI::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    'exception' => true
])->connect();

var_dump($oci);
