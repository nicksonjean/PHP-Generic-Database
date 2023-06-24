<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/JSON/pdo_mysql.json')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/JSON/pdo_pgsql.json')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/JSON/pdo_sqlsrv.json')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/JSON/pdo_oci.json')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/JSON/pdo_firebird.json')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/JSON/pdo_sqlite.json')->connect();

var_dump($sqlite);
