<?php

use
    GenericDatabase\Engine\OCIEngine,

    GenericDatabase\Engine\OCI\OCI;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$oci = OCIEngine::new(
    $_ENV['OCI_HOST'],
    +$_ENV['OCI_PORT'],
    $_ENV['OCI_DATABASE'],
    $_ENV['OCI_USER'],
    $_ENV['OCI_PASSWORD'],
    'utf8',
    [
        OCI::ATTR_PERSISTENT => true,
        OCI::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    true
)->connect();

var_dump($oci);
