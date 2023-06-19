<?php

use
  GenericDatabase\Connection,
  GenericDatabase\Engine\MySQli\MySQL,
  GenericDatabase\Engine\PgSQL\PgSQL,
  GenericDatabase\Engine\SQLSrv\SQLSrv,
  GenericDatabase\Engine\OCI\OCI,
  GenericDatabase\Engine\FBird\FBird,
  GenericDatabase\Engine\SQLite3\SQLite;

require_once __DIR__ . '/../vendor/autoload.php';

$context = Connection::new('mysqli', 'localhost', 3306, 'demodev', 'root', '', 'utf8', [
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

$context = Connection::new('pgsql', 'localhost', 5432, 'postgres', 'postgres', 'masterkey', 'utf8', [
  PgSQL::ATTR_PERSISTENT => true,
  PgSQL::ATTR_CONNECT_ASYNC => true,
  PgSQL::ATTR_CONNECT_FORCE_NEW => true,
  PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('sqlsrv', 'localhost', 1433, 'demodev', 'sa', 'masterkey', 'utf8', [
  SQLSrv::ATTR_PERSISTENT => true,
  SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('oci', 'localhost', 1521, 'xe', 'hr', 'masterkey', 'utf8', [
  OCI::ATTR_PERSISTENT => true,
  OCI::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('fbird', 'localhost', 3050, '../assets/DB.FDB', 'sysdba', 'masterkey', 'utf8', [
  FBird::ATTR_PERSISTENT => true,
  FBird::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

var_dump($context);

$context = Connection::new('sqlite3', '../assets/DB.SQLITE', 'utf8', [
  SQLite::ATTR_OPEN_READONLY => false,
  SQLite::ATTR_OPEN_READWRITE => true,
  SQLite::ATTR_OPEN_CREATE => true,
  SQLite::ATTR_CONNECT_TIMEOUT => 28800,
  SQLite::ATTR_PERSISTENT => true,
  SQLite::ATTR_AUTOCOMMIT => true
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'mysql', 'localhost', 3306, 'demodev', 'root', '', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'pgsql', 'localhost', 5432, 'postgres', 'postgres', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'sqlsrv', 'localhost', 1433, 'demodev', 'sa', 'masterkey', 'utf8', [
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'oci', 'localhost', 1521, 'xe', 'hr', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'firebird', 'localhost', 3050, '../assets/DB.FDB', 'sysdba', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);

$context = Connection::new('pdo', 'sqlite', '../assets/DB.SQLITE', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($context);
