<?php
require '../engine/PDO.php';

$mysql = PDOEngine::new('./JSON/mysql.json')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('./JSON/pgsql.json')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('./JSON/sqlsrv.json')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('./JSON/oci.json')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('./JSON/firebird.json')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('./JSON/sqlite.json')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
