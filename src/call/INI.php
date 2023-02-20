<?php

use GenericDatabase\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('./INI/mysql.ini')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('./INI/pgsql.ini')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('./INI/sqlsrv.ini')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('./INI/oci.ini')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('./INI/firebird.ini')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('./INI/sqlite.ini')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
