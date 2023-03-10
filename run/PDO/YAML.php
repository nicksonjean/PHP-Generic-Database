<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/YAML/mysql.yaml')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/YAML/pgsql.yaml')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/YAML/sqlsrv.yaml')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/YAML/oci.yaml')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/YAML/firebird.yaml')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/YAML/sqlite.yaml')->connect();

var_dump($sqlite);
