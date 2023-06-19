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

$context = Connection
  ::setEngine('mysqli')
  ::setHost('localhost')
  ::setPort(3306)
  ::setDatabase('demodev')
  ::setUser('root')
  ::setPassword('')
  ::setCharset('utf8')
  ::setOptions([
    MySQL::ATTR_PERSISTENT => true,
    MySQL::ATTR_AUTOCOMMIT => true,
    MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    MySQL::ATTR_SET_CHARSET_NAME => "utf8",
    MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
    MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
    MySQL::ATTR_OPT_READ_TIMEOUT => 30,
    MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pgsql')
  ::setHost('localhost')
  ::setPort(5432)
  ::setDatabase('postgres')
  ::setUser('postgres')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    PgSQL::ATTR_PERSISTENT => true,
    PgSQL::ATTR_CONNECT_ASYNC => true,
    PgSQL::ATTR_CONNECT_FORCE_NEW => true,
    PgSQL::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('sqlsrv')
  ::setHost('localhost')
  ::setPort(1433)
  ::setDatabase('demodev')
  ::setUser('sa')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    SQLSrv::ATTR_PERSISTENT => true,
    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('oci')
  ::setHost('localhost')
  ::setPort(1521)
  ::setDatabase('xe')
  ::setUser('hr')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    OCI::ATTR_PERSISTENT => true,
    OCI::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('fbird')
  ::setHost('localhost')
  ::setPort(3050)
  ::setDatabase('../assets/DB.FDB')
  ::setUser('sysdba')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    FBird::ATTR_PERSISTENT => true,
    FBird::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('sqlite3')
  ::setDatabase('../assets/DB.SQLITE')
  ::setCharset('utf8')
  ::setOptions([
    SQLite::ATTR_OPEN_READONLY => false,
    SQLite::ATTR_OPEN_READWRITE => true,
    SQLite::ATTR_OPEN_CREATE => true,
    SQLite::ATTR_CONNECT_TIMEOUT => 28800,
    SQLite::ATTR_PERSISTENT => true,
    SQLite::ATTR_AUTOCOMMIT => true
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pdo')
  ::setDriver('mysql')
  ::setHost('localhost')
  ::setPort(3306)
  ::setDatabase('demodev')
  ::setUser('root')
  ::setPassword('')
  ::setCharset('utf8')
  ::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pdo')
  ::setDriver('pgsql')
  ::setHost('localhost')
  ::setPort(5432)
  ::setDatabase('postgres')
  ::setUser('postgres')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pdo')
  ::setDriver('sqlsrv')
  ::setHost('localhost')
  ::setPort(1433)
  ::setDatabase('demodev')
  ::setUser('sa')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pdo')
  ::setDriver('oci')
  ::setHost('localhost')
  ::setPort(1521)
  ::setDatabase('xe')
  ::setUser('hr')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pdo')
  ::setDriver('firebird')
  ::setHost('localhost')
  ::setPort(3050)
  ::setDatabase('../assets/DB.FDB')
  ::setUser('sysdba')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ::setException(true)
  ->connect();

var_dump($context);

$context = Connection
  ::setEngine('pdo')
  ::setDriver('sqlite')
  ::setDatabase('../assets/DB.SQLITE')
  ::setCharset('utf8')
  ::setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ::setException(true)
  ->connect();

var_dump($context);
