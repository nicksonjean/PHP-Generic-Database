<?php

use
    GenericDatabase\Engine\SQLSrvEngine,

    GenericDatabase\Engine\SQLSrv\SQLSrv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$sqlsrv = SQLSrvEngine::new([
    'host' => $_ENV['SQLSRV_HOST'],
    'port' => +$_ENV['SQLSRV_PORT'],
    'database' => $_ENV['SQLSRV_DATABASE'],
    'user' => $_ENV['SQLSRV_USER'],
    'password' => $_ENV['SQLSRV_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        SQLSrv::ATTR_PERSISTENT => true,
        SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    'exception' => true
])->connect();

var_dump($sqlsrv);
