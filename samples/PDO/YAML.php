<?php

use GenericDatabase\Engine\PDOConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOConnection::new(PATH_ROOT . '/resources/dsn/yaml/pdo_mysql.yaml')->connect();

var_dump($mysql);

$pgsql = PDOConnection::new(PATH_ROOT . '/resources/dsn/yaml/pdo_pgsql.yaml')->connect();

var_dump($pgsql);

$sqlsrv = PDOConnection::new(PATH_ROOT . '/resources/dsn/yaml/pdo_sqlsrv.yaml')->connect();

var_dump($sqlsrv);

$oci = PDOConnection::new(PATH_ROOT . '/resources/dsn/yaml/pdo_oci.yaml')->connect();

var_dump($oci);

$firebird = PDOConnection::new(PATH_ROOT . '/resources/dsn/yaml/pdo_firebird.yaml')->connect();

var_dump($firebird);

$sqlite = PDOConnection::new(PATH_ROOT . '/resources/dsn/yaml/pdo_sqlite.yaml')->connect();

var_dump($sqlite);
