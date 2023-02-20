<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../vendor/autoload.php';

$mysql = PDOEngine::new('../assets/XML/mysql.xml')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('../assets/XML/pgsql.xml')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('../assets/XML/sqlsrv.xml')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('../assets/XML/oci.xml')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('../assets/XML/firebird.xml')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('../assets/XML/sqlite.xml')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
