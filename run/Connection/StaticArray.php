<?php

use GenericDatabase\Connection;
use GenericDatabase\Engine\MySQli\MySQL;
use GenericDatabase\Engine\PgSQL\PgSQL;
use GenericDatabase\Engine\SQLSrv\SQLSrv;
use GenericDatabase\Engine\OCI\OCI;
use GenericDatabase\Engine\FBird\FBird;
use GenericDatabase\Engine\SQLite\SQLite;
use Dotenv\Dotenv;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

Dotenv::createImmutable(PATH_ROOT)->load();

$context = Connection::new([
    'engine' => 'mysqli',
    'host' => $_ENV['MYSQL_HOST'],
    'port' => +$_ENV['MYSQL_PORT'],
    'database' => $_ENV['MYSQL_DATABASE'],
    'user' => $_ENV['MYSQL_USER'],
    'password' => $_ENV['MYSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        MySQL::ATTR_PERSISTENT => true,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ],
    'exception' => true
])->connect();

var_dump($context);

$context = Connection::new([
    'engine' => 'pgsql',
    'host' => $_ENV['PGSQL_HOST'],
    'port' => +$_ENV['PGSQL_PORT'],
    'database' => $_ENV['PGSQL_DATABASE'],
    'user' => $_ENV['PGSQL_USER'],
    'password' => $_ENV['PGSQL_PASSWORD'],
    'charset' => 'utf8',
    'options' => [
        PgSQL::ATTR_PERSISTENT => true,
        PgSQL::ATTR_CONNECT_ASYNC => true,
        PgSQL::ATTR_CONNECT_FORCE_NEW => true,
        PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
    ],
    'exception' => true
])->connect();

var_dump($context);

$context = Connection::new([
    'engine' => 'sqlsrv',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'oci',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'fbird',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'sqlite',
    'database' => $_ENV['SQLITE_DATABASE'],
    'charset' => 'utf8',
    'options' => [
        SQLite::ATTR_OPEN_READONLY => false,
        SQLite::ATTR_OPEN_READWRITE => true,
        SQLite::ATTR_OPEN_CREATE => true,
        SQLite::ATTR_CONNECT_TIMEOUT => 28800,
        SQLite::ATTR_PERSISTENT => true,
        SQLite::ATTR_AUTOCOMMIT => true
    ],
    'exception' => true
])->connect();

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
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

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
    'driver' => 'sqlite',
    'database' => $_ENV['SQLITE_DATABASE'],
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ], 'exception' => true
])->connect();

var_dump($context);

$context = Connection::new([
    'engine' => 'pdo',
    'driver' => 'sqlite',
    'database' => 'memory',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ], 'exception' => true
])->connect();

var_dump($context);
