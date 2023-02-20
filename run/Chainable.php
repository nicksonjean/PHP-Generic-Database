<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../vendor/autoload.php';

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

// $mysql->loadFromFile('../../tests/test.sql');

echo "<pre>";
var_dump($mysql);
echo "</pre>";

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

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

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

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

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

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = new PDOEngine();
$firebird->setDriver('firebird')
  ->setHost('localhost')
  ->setPort(3050)
  ->setDatabase('../assets/DB.FDB')
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

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = new PDOEngine();
$sqlite->setDriver('sqlite')
  ->setDatabase('../assets/DB.SQLITE')
  ->setCharset('utf8')
  ->setOptions([
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
  ])
  ->setException(true)
  ->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
