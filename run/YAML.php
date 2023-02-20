<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../vendor/autoload.php';

$mysql = PDOEngine::new('../assets/YAML/mysql.yaml')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('../assets/YAML/pgsql.yaml')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('../assets/YAML/sqlsrv.yaml')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('../assets/YAML/oci.yaml')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('../assets/YAML/firebird.yaml')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('../assets/YAML/sqlite.yaml')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
