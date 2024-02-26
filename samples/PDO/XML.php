<?php

use GenericDatabase\Engine\PDOEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOEngine::new(PATH_ROOT . '/resources/dsn/xml/pdo_mysql.xml')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new(PATH_ROOT . '/resources/dsn/xml/pdo_pgsql.xml')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new(PATH_ROOT . '/resources/dsn/xml/pdo_sqlsrv.xml')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new(PATH_ROOT . '/resources/dsn/xml/pdo_oci.xml')->connect();

var_dump($oci);

$firebird = PDOEngine::new(PATH_ROOT . '/resources/dsn/xml/pdo_firebird.xml')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new(PATH_ROOT . '/resources/dsn/xml/pdo_sqlite.xml')->connect();

var_dump($sqlite);
