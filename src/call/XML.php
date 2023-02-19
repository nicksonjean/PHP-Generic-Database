<?php
require '../engine/PDO.php';

$mysql = PDOEngine::new('./XML/mysql.xml')->connect();

echo "<pre>";
var_dump($mysql);
echo "</pre>";

$pgsql = PDOEngine::new('./XML/pgsql.xml')->connect();

echo "<pre>";
var_dump($pgsql);
echo "</pre>";

$sqlsrv = PDOEngine::new('./XML/sqlsrv.xml')->connect();

echo "<pre>";
var_dump($sqlsrv);
echo "</pre>";

$oci = PDOEngine::new('./XML/oci.xml')->connect();

echo "<pre>";
var_dump($oci);
echo "</pre>";

$firebird = PDOEngine::new('./XML/firebird.xml')->connect();

echo "<pre>";
var_dump($firebird);
echo "</pre>";

$sqlite = PDOEngine::new('./XML/sqlite.xml')->connect();

echo "<pre>";
var_dump($sqlite);
echo "</pre>";
