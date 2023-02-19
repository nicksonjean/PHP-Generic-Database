<?php
require '../engine/PDO.php';

$mysql = PDOEngine::new('./YAML/mysql.yaml')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('./YAML/pgsql.yaml')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('./YAML/sqlsrv.yaml')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('./YAML/oci.yaml')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('./YAML/firebird.yaml')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('./YAML/sqlite.yaml')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
