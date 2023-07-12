<?php

use
    GenericDatabase\Engine\SQLSrvEngine,

    GenericDatabase\Engine\SQLSrv\SQLSrv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$sqlsrv = SQLSrvEngine::new(
    $_ENV['SQLSRV_HOST'],
    +$_ENV['SQLSRV_PORT'],
    $_ENV['SQLSRV_DATABASE'],
    $_ENV['SQLSRV_USER'],
    $_ENV['SQLSRV_PASSWORD'],
    'utf8',
    [
        SQLSrv::ATTR_PERSISTENT => true,
        SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    true
)->connect();

var_dump($sqlsrv);
