<?php

use GenericDatabase\Engine\PDOConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOConnection::new(PATH_ROOT . '/resources/dsn/json/pdo_mysql.json')->connect();

var_dump($mysql);

$pgsql = PDOConnection::new(PATH_ROOT . '/resources/dsn/json/pdo_pgsql.json')->connect();

var_dump($pgsql);

$sqlsrv = PDOConnection::new(PATH_ROOT . '/resources/dsn/json/pdo_sqlsrv.json')->connect();

var_dump($sqlsrv);

$oci = PDOConnection::new(PATH_ROOT . '/resources/dsn/json/pdo_oci.json')->connect();

var_dump($oci);

$firebird = PDOConnection::new(PATH_ROOT . '/resources/dsn/json/pdo_firebird.json')->connect();

var_dump($firebird);

$sqlite = PDOConnection::new(PATH_ROOT . '/resources/dsn/json/pdo_sqlite.json')->connect();

var_dump($sqlite);
