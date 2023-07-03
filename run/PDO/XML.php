<?php

use GenericDatabase\Engine\PDOEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOEngine::new(PATH_ROOT . '/assets/XML/pdo_mysql.xml')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new(PATH_ROOT . '/assets/XML/pdo_pgsql.xml')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new(PATH_ROOT . '/assets/XML/pdo_sqlsrv.xml')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new(PATH_ROOT . '/assets/XML/pdo_oci.xml')->connect();

var_dump($oci);

$firebird = PDOEngine::new(PATH_ROOT . '/assets/XML/pdo_firebird.xml')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new(PATH_ROOT . '/assets/XML/pdo_sqlite.xml')->connect();

var_dump($sqlite);
