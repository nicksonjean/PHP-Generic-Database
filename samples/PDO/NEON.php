<?php

use GenericDatabase\Engine\PDOConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = PDOConnection::new(PATH_ROOT . '/resources/dsn/neon/pdo_mysql.neon')->connect();

var_dump($mysql);

$pgsql = PDOConnection::new(PATH_ROOT . '/resources/dsn/neon/pdo_pgsql.neon')->connect();

var_dump($pgsql);

$sqlsrv = PDOConnection::new(PATH_ROOT . '/resources/dsn/neon/pdo_sqlsrv.neon')->connect();

var_dump($sqlsrv);

$oci = PDOConnection::new(PATH_ROOT . '/resources/dsn/neon/pdo_oci.neon')->connect();

var_dump($oci);

$firebird = PDOConnection::new(PATH_ROOT . '/resources/dsn/neon/pdo_firebird.neon')->connect();

var_dump($firebird);

$sqlite = PDOConnection::new(PATH_ROOT . '/resources/dsn/neon/pdo_sqlite.neon')->connect();

var_dump($sqlite);
