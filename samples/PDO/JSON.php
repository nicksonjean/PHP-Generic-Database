<?php

use GenericDatabase\Engine\PDOEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOEngine::new(PATH_ROOT . '/resources/JSON/pdo_mysql.json')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new(PATH_ROOT . '/resources/JSON/pdo_pgsql.json')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new(PATH_ROOT . '/resources/JSON/pdo_sqlsrv.json')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new(PATH_ROOT . '/resources/JSON/pdo_oci.json')->connect();

var_dump($oci);

$firebird = PDOEngine::new(PATH_ROOT . '/resources/JSON/pdo_firebird.json')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new(PATH_ROOT . '/resources/JSON/pdo_sqlite.json')->connect();

var_dump($sqlite);