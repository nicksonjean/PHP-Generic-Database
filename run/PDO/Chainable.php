<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = new PDOEngine();
$mysql->setDriver('mysql')
  ->setHost('localhost')
  ->setPort(3306)
  ->setDatabase('demodev')
  ->setUser('root')
  ->setPassword('')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

// $mysql->loadFromFile('../../../tests/test.sql');

var_dump($mysql);

$pgsql = new PDOEngine();
$pgsql->setDriver('pgsql')
  ->setHost('localhost')
  ->setPort(5432)
  ->setDatabase('postgres')
  ->setUser('postgres')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

var_dump($pgsql);

$sqlsrv = new PDOEngine();
$sqlsrv->setDriver('sqlsrv')
  ->setHost('localhost')
  ->setPort(1433)
  ->setDatabase('demodev')
  ->setUser('sa')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

var_dump($sqlsrv);

$oci = new PDOEngine();
$oci->setDriver('oci')
  ->setHost('localhost')
  ->setPort(1521)
  ->setDatabase('xe')
  ->setUser('hr')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

var_dump($oci);

$firebird = new PDOEngine();
$firebird->setDriver('firebird')
  ->setHost('localhost')
  ->setPort(3050)
  ->setDatabase('../../assets/DB.FDB')
  ->setUser('sysdba')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

var_dump($firebird);

$sqlite = new PDOEngine();
$sqlite->setDriver('sqlite')
  ->setDatabase('../../assets/DB.SQLITE')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

var_dump($sqlite);

$memory = new PDOEngine();
$memory->setDriver('sqlite')
  ->setDatabase('memory')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

var_dump($memory);
