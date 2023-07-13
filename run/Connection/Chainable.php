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
$context = new Connection();

$context->setEngine('mysqli')
    ->setHost($_ENV['MYSQL_HOST'])
    ->setPort(+$_ENV['MYSQL_PORT'])
    ->setDatabase($_ENV['MYSQL_DATABASE'])
    ->setUser($_ENV['MYSQL_USER'])
    ->setPassword($_ENV['MYSQL_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        MySQL::ATTR_PERSISTENT => true,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pgsql')
    ->setHost($_ENV['PGSQL_HOST'])
    ->setPort(+$_ENV['PGSQL_PORT'])
    ->setDatabase($_ENV['PGSQL_DATABASE'])
    ->setUser($_ENV['PGSQL_USER'])
    ->setPassword($_ENV['PGSQL_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        PgSQL::ATTR_PERSISTENT => true,
        PgSQL::ATTR_CONNECT_ASYNC => true,
        PgSQL::ATTR_CONNECT_FORCE_NEW => true,
        PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('sqlsrv')
    ->setHost($_ENV['SQLSRV_HOST'])
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

var_dump($context);

$context->setEngine('oci')
    ->setHost($_ENV['OCI_HOST'])
    ->setPort(+$_ENV['OCI_PORT'])
    ->setDatabase($_ENV['OCI_DATABASE'])
    ->setUser($_ENV['OCI_USER'])
    ->setPassword($_ENV['OCI_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        OCI::ATTR_PERSISTENT => true,
        OCI::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('fbird')
    ->setHost($_ENV['FBIRD_HOST'])
    ->setPort(+$_ENV['FBIRD_PORT'])
    ->setDatabase($_ENV['FBIRD_DATABASE'])
    ->setUser($_ENV['FBIRD_USER'])
    ->setPassword($_ENV['FBIRD_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        FBird::ATTR_PERSISTENT => true,
        FBird::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('sqlite')
    ->setDatabase($_ENV['SQLITE_DATABASE'])
    ->setCharset('utf8')
    ->setOptions([
        SQLite::ATTR_OPEN_READONLY => false,
        SQLite::ATTR_OPEN_READWRITE => true,
        SQLite::ATTR_OPEN_CREATE => true,
        SQLite::ATTR_CONNECT_TIMEOUT => 28800,
        SQLite::ATTR_PERSISTENT => true,
        SQLite::ATTR_AUTOCOMMIT => true
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('mysql')
    ->setHost($_ENV['MYSQL_HOST'])
    ->setPort(+$_ENV['MYSQL_PORT'])
    ->setDatabase($_ENV['MYSQL_DATABASE'])
    ->setUser($_ENV['MYSQL_USER'])
    ->setPassword($_ENV['MYSQL_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('pgsql')
    ->setHost($_ENV['PGSQL_HOST'])
    ->setPort(+$_ENV['PGSQL_PORT'])
    ->setDatabase($_ENV['PGSQL_DATABASE'])
    ->setUser($_ENV['PGSQL_USER'])
    ->setPassword($_ENV['PGSQL_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('sqlsrv')
    ->setHost($_ENV['SQLSRV_HOST'])
    ->setPort(+$_ENV['SQLSRV_PORT'])
    ->setDatabase($_ENV['SQLSRV_DATABASE'])
    ->setUser($_ENV['SQLSRV_USER'])
    ->setPassword($_ENV['SQLSRV_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('oci')
    ->setHost($_ENV['OCI_HOST'])
    ->setPort(+$_ENV['OCI_PORT'])
    ->setDatabase($_ENV['OCI_DATABASE'])
    ->setUser($_ENV['OCI_USER'])
    ->setPassword($_ENV['OCI_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('firebird')
    ->setHost($_ENV['FBIRD_HOST'])
    ->setPort(+$_ENV['FBIRD_PORT'])
    ->setDatabase($_ENV['FBIRD_DATABASE'])
    ->setUser($_ENV['FBIRD_USER'])
    ->setPassword($_ENV['FBIRD_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('sqlite')
    ->setDatabase($_ENV['SQLITE_DATABASE'])
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);

$context->setEngine('pdo')
    ->setDriver('sqlite')
    ->setDatabase('memory')
    ->setCharset('utf8')
    ->setOptions([
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ])
    ->setException(true)
    ->connect();

var_dump($context);
