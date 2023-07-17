<?php

use GenericDatabase\Engine\PDOEngine;
use PDO;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$mysql = PDOEngine
    ::setDriver('mysql')
    ::setHost($_ENV['MYSQL_HOST'])
    ::setPort(+$_ENV['MYSQL_PORT'])
    ::setDatabase($_ENV['MYSQL_DATABASE'])
    ::setUser($_ENV['MYSQL_USER'])
    ::setPassword($_ENV['MYSQL_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($mysql);

$pgsql = PDOEngine
    ::setDriver('pgsql')
    ::setHost($_ENV['PGSQL_HOST'])
    ::setPort(+$_ENV['PGSQL_PORT'])
    ::setDatabase($_ENV['PGSQL_DATABASE'])
    ::setUser($_ENV['PGSQL_USER'])
    ::setPassword($_ENV['PGSQL_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine
    ::setDriver('sqlsrv')
    ::setHost($_ENV['SQLSRV_HOST'])
    ::setPort(+$_ENV['SQLSRV_PORT'])
    ::setDatabase($_ENV['SQLSRV_DATABASE'])
    ::setUser($_ENV['SQLSRV_USER'])
    ::setPassword($_ENV['SQLSRV_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($sqlsrv);

$oci = PDOEngine
    ::setDriver('oci')
    ::setHost($_ENV['OCI_HOST'])
    ::setPort(+$_ENV['OCI_PORT'])
    ::setDatabase($_ENV['OCI_DATABASE'])
    ::setUser($_ENV['OCI_USER'])
    ::setPassword($_ENV['OCI_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($oci);

$firebird = PDOEngine
    ::setDriver('firebird')
    ::setHost($_ENV['FBIRD_HOST'])
    ::setPort(+$_ENV['FBIRD_PORT'])
    ::setDatabase($_ENV['FBIRD_DATABASE'])
    ::setUser($_ENV['FBIRD_USER'])
    ::setPassword($_ENV['FBIRD_PASSWORD'])
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($firebird);

$sqlite = PDOEngine
    ::setDriver('sqlite')
    ::setDatabase($_ENV['SQLITE_DATABASE'])
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($sqlite);

$memory = PDOEngine
    ::setDriver('sqlite')
    ::setDatabase('memory')
    ::setCharset('utf8')
    ::setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ::setException(true)
    ->connect();

var_dump($memory);
