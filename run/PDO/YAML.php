<?php

use GenericDatabase\Engine\PDOEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOEngine::new(PATH_ROOT . '/assets/YAML/pdo_mysql.yaml')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new(PATH_ROOT . '/assets/YAML/pdo_pgsql.yaml')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new(PATH_ROOT . '/assets/YAML/pdo_sqlsrv.yaml')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new(PATH_ROOT . '/assets/YAML/pdo_oci.yaml')->connect();

var_dump($oci);

$firebird = PDOEngine::new(PATH_ROOT . '/assets/YAML/pdo_firebird.yaml')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new(PATH_ROOT . '/assets/YAML/pdo_sqlite.yaml')->connect();

var_dump($sqlite);
