<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/JSON/mysql.json')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/JSON/pgsql.json')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/JSON/sqlsrv.json')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/JSON/oci.json')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/JSON/firebird.json')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/JSON/sqlite.json')->connect();

var_dump($sqlite);
