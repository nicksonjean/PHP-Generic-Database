<?php

use
    GenericDatabase\Engine\SQLSrvEngine,

    GenericDatabase\Engine\SQLSrv\SQLSrv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$sqlsrv = new SQLSrvEngine();
$sqlsrv->setHost($_ENV['SQLSRV_HOST'])
    ->setPort(+$_ENV['SQLSRV_PORT'])
    ->setDatabase($_ENV['SQLSRV_DATABASE'])
    ->setUser($_ENV['SQLSRV_USER'])
    ->setPassword($_ENV['SQLSRV_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        SQLSrv::ATTR_PERSISTENT => true,
        SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ->setException(true)
    ->connect();

var_dump($sqlsrv);
