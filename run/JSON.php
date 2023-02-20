<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../vendor/autoload.php';

$mysql = PDOEngine::new('../assets/JSON/mysql.json')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('../assets/JSON/pgsql.json')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('../assets/JSON/sqlsrv.json')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('../assets/JSON/oci.json')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('../assets/JSON/firebird.json')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('../assets/JSON/sqlite.json')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
