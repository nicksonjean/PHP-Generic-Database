<?php

use GenericDatabase\Engine\PDOEngine;
use PDO;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$mysql = PDOEngine::new(
    'mysql',
    $_ENV['MYSQL_HOST'],
    +$_ENV['MYSQL_PORT'],
    $_ENV['MYSQL_DATABASE'],
    $_ENV['MYSQL_USER'],
    $_ENV['MYSQL_PASSWORD'],
    'utf8',
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($mysql);

$pgsql = PDOEngine::new(
    'pgsql',
    $_ENV['PGSQL_HOST'],
    +$_ENV['PGSQL_PORT'],
    $_ENV['PGSQL_DATABASE'],
    $_ENV['PGSQL_USER'],
    $_ENV['PGSQL_PASSWORD'],
    'utf8',
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new(
    'sqlsrv',
    $_ENV['SQLSRV_HOST'],
    +$_ENV['SQLSRV_PORT'],
    $_ENV['SQLSRV_DATABASE'],
    $_ENV['SQLSRV_USER'],
    $_ENV['SQLSRV_PASSWORD'],
    'utf8',
    [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new(
    'oci',
    $_ENV['OCI_HOST'],
    +$_ENV['OCI_PORT'],
    $_ENV['OCI_DATABASE'],
    $_ENV['OCI_USER'],
    $_ENV['OCI_PASSWORD'],
    'utf8',
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($oci);

$firebird = PDOEngine::new(
    'firebird',
    $_ENV['FBIRD_HOST'],
    +$_ENV['FBIRD_PORT'],
    $_ENV['FBIRD_DATABASE'],
    $_ENV['FBIRD_USER'],
    $_ENV['FBIRD_PASSWORD'],
    'utf8',
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($firebird);

$sqlite2 = PDOEngine::new(
    'sqlite',
    $_ENV['SQLITE_DATABASE'],
    'utf8',
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($sqlite2);

$memory = PDOEngine::new(
    'sqlite',
    'memory',
    'utf8',
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ],
    true
)->connect();

var_dump($memory);
