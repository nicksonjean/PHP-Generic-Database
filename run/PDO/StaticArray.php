<?php

use GenericDatabase\Engine\PDOEngine;
use PDO;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$mysql = PDOEngine::new([
    'driver' => 'mysql',
    'host' => $_ENV['MYSQL_HOST'],
    'port' => +$_ENV['MYSQL_PORT'],
    'database' => $_ENV['MYSQL_DATABASE'],
    'user' => $_ENV['MYSQL_USER'],
    'password' => $_ENV['MYSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($mysql);

$pgsql = PDOEngine::new([
    'driver' => 'pgsql',
    'host' => $_ENV['PGSQL_HOST'],
    'port' => +$_ENV['PGSQL_PORT'],
    'database' => $_ENV['PGSQL_DATABASE'],
    'user' => $_ENV['PGSQL_USER'],
    'password' => $_ENV['PGSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new([
    'driver' => 'sqlsrv',
    'host' => $_ENV['SQLSRV_HOST'],
    'port' => +$_ENV['SQLSRV_PORT'],
    'database' => $_ENV['SQLSRV_DATABASE'],
    'user' => $_ENV['SQLSRV_USER'],
    'password' => $_ENV['SQLSRV_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new([
    'driver' => 'oci',
    'host' => $_ENV['OCI_HOST'],
    'port' => +$_ENV['OCI_PORT'],
    'database' => $_ENV['OCI_DATABASE'],
    'user' => $_ENV['OCI_USER'],
    'password' => $_ENV['OCI_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($oci);

$firebird = PDOEngine::new([
    'driver' => 'firebird',
    'host' => $_ENV['FBIRD_HOST'],
    'port' => +$_ENV['FBIRD_PORT'],
    'database' => $_ENV['FBIRD_DATABASE'],
    'user' => $_ENV['FBIRD_USER'],
    'password' => $_ENV['FBIRD_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($firebird);

$sqlite2 = PDOEngine::new([
    'driver' => 'sqlite',
    'database' => $_ENV['SQLITE_DATABASE'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($sqlite2);

$memory = PDOEngine::new([
    'driver' => 'sqlite',
    'database' => 'memory',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    'exception' => true
])->connect();

var_dump($memory);
