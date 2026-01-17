<?php

use GenericDatabase\Engine\PDOConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOConnection::new(PATH_ROOT . '/resources/dsn/ini/pdo_mysql.ini')->connect();

var_dump($mysql);

$pgsql = PDOConnection::new(PATH_ROOT . '/resources/dsn/ini/pdo_pgsql.ini')->connect();

var_dump($pgsql);

$sqlsrv = PDOConnection::new(PATH_ROOT . '/resources/dsn/ini/pdo_sqlsrv.ini')->connect();

var_dump($sqlsrv);

$oci = PDOConnection::new(PATH_ROOT . '/resources/dsn/ini/pdo_oci.ini')->connect();

var_dump($oci);

$firebird = PDOConnection::new(PATH_ROOT . '/resources/dsn/ini/pdo_firebird.ini')->connect();

var_dump($firebird);

$sqlite = PDOConnection::new(PATH_ROOT . '/resources/dsn/ini/pdo_sqlite.ini')->connect();

var_dump($sqlite);
