<?php

use
    GenericDatabase\Connection,

    GenericDatabase\Engine\MySQli\MySQL,
    GenericDatabase\Engine\PgSQL\PgSQL,
    GenericDatabase\Engine\SQLSrv\SQLSrv,
    GenericDatabase\Engine\OCI\OCI,
    GenericDatabase\Engine\FBird\FBird,
    GenericDatabase\Engine\SQLite\SQLite;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$context = Connection::new('mysqli', $_ENV['MYSQL_HOST'], +$_ENV['MYSQL_PORT'], $_ENV['MYSQL_DATABASE'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD'], 'utf8', [
    MySQL::ATTR_PERSISTENT => true,
    MySQL::ATTR_AUTOCOMMIT => true,
    MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    MySQL::ATTR_SET_CHARSET_NAME => "utf8",
    MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
    MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
    MySQL::ATTR_OPT_READ_TIMEOUT => 30,
    MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
], true)->connect();

var_dump($context);

$context = Connection::new('pgsql', $_ENV['PGSQL_HOST'], +$_ENV['PGSQL_PORT'], $_ENV['PGSQL_DATABASE'], $_ENV['PGSQL_USER'], $_ENV['PGSQL_PASSWORD'], 'utf8', [
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('sqlsrv', $_ENV['SQLSRV_HOST'], +$_ENV['SQLSRV_PORT'], $_ENV['SQLSRV_DATABASE'], $_ENV['SQLSRV_USER'], $_ENV['SQLSRV_PASSWORD'], 'utf8', [
    SQLSrv::ATTR_PERSISTENT => true,
    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('oci', $_ENV['OCI_HOST'], +$_ENV['OCI_PORT'], $_ENV['OCI_DATABASE'], $_ENV['OCI_USER'], $_ENV['OCI_PASSWORD'], 'utf8', [
    OCI::ATTR_PERSISTENT => true,
    OCI::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('fbird', $_ENV['FBIRD_HOST'], +$_ENV['FBIRD_PORT'], $_ENV['FBIRD_DATABASE'], $_ENV['FBIRD_USER'], $_ENV['FBIRD_PASSWORD'], 'utf8', [
    FBird::ATTR_PERSISTENT => true,
    FBird::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('sqlite', $_ENV['SQLITE_DATABASE'], 'utf8', [
    SQLite::ATTR_OPEN_READONLY => false,
    SQLite::ATTR_OPEN_READWRITE => true,
    SQLite::ATTR_OPEN_CREATE => true,
    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
    SQLite::ATTR_PERSISTENT => true,
    SQLite::ATTR_AUTOCOMMIT => true
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'mysql', $_ENV['MYSQL_HOST'], +$_ENV['MYSQL_PORT'], $_ENV['MYSQL_DATABASE'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD'], 'utf8', [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'pgsql', $_ENV['PGSQL_HOST'], +$_ENV['PGSQL_PORT'], $_ENV['PGSQL_DATABASE'], $_ENV['PGSQL_USER'], $_ENV['PGSQL_PASSWORD'], 'utf8', [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'sqlsrv', $_ENV['SQLSRV_HOST'], +$_ENV['SQLSRV_PORT'], $_ENV['SQLSRV_DATABASE'], $_ENV['SQLSRV_USER'], $_ENV['SQLSRV_PASSWORD'], 'utf8', [
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'oci', $_ENV['OCI_HOST'], +$_ENV['OCI_PORT'], $_ENV['OCI_DATABASE'], $_ENV['OCI_USER'], $_ENV['OCI_PASSWORD'], 'utf8', [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'firebird', $_ENV['FBIRD_HOST'], +$_ENV['FBIRD_PORT'], $_ENV['FBIRD_DATABASE'], $_ENV['FBIRD_USER'], $_ENV['FBIRD_PASSWORD'], 'utf8', [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'sqlite', $_ENV['SQLITE_DATABASE'], 'utf8', [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);
