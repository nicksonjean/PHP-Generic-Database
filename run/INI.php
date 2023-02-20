<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../vendor/autoload.php';

$mysql = PDOEngine::new('../assets/INI/mysql.ini')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('../assets/INI/pgsql.ini')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('../assets/INI/sqlsrv.ini')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('../assets/INI/oci.ini')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('../assets/INI/firebird.ini')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('../assets/INI/sqlite.ini')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
