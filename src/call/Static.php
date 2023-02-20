<?php

use GenericDatabase\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('mysql', 'localhost', 3306, 'demodev', 'root', '', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

$mysql->loadFromFile('../../tests/test.sql');

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('pgsql', 'localhost', 5432, 'postgres', 'postgres', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('sqlsrv', 'localhost', 1433, 'demodev', 'sa', 'masterkey', 'utf8', [
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('oci', 'localhost', 1521, 'xe', 'hr', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('firebird', 'localhost', 3050, '../../assets/DB.FDB', 'sysdba', 'masterkey', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite2 = PDOEngine::new('sqlite', '../../assets/DB.SQLITE', 'utf8', [
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_EMULATE_PREPARES => true,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
], true)->connect();

echo "<pre>";
var_dump($sqlite2);
echo "</pre>";
