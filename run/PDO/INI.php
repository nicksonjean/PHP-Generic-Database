<?php

use GenericDatabase\Engine\PDOEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOEngine::new(PATH_ROOT . '/assets/INI/pdo_mysql.ini')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new(PATH_ROOT . '/assets/INI/pdo_pgsql.ini')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new(PATH_ROOT . '/assets/INI/pdo_sqlsrv.ini')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new(PATH_ROOT . '/assets/INI/pdo_oci.ini')->connect();

var_dump($oci);

$firebird = PDOEngine::new(PATH_ROOT . '/assets/INI/pdo_firebird.ini')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new(PATH_ROOT . '/assets/INI/pdo_sqlite.ini')->connect();

var_dump($sqlite);
