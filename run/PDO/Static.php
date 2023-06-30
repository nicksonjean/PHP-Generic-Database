<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('mysql', 'localhost', 3306, 'demodev', 'root', '', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

// $mysql->loadFromFile('../../tests/test.sql');

var_dump($mysql);

$pgsql = PDOEngine::new('pgsql', 'localhost', 5432, 'postgres', 'postgres', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('sqlsrv', 'localhost', 1433, 'demodev', 'sa', 'masterkey', 'utf8', [
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('oci', 'localhost', 1521, 'xe', 'hr', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($oci);

$firebird = PDOEngine::new('firebird', 'localhost', 3050, '../../assets/DB.FDB', 'sysdba', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($firebird);

$sqlite2 = PDOEngine::new('sqlite', '../../assets/DB.SQLITE', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($sqlite2);


$memory = PDOEngine::new('sqlite', 'memory', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

var_dump($memory);
