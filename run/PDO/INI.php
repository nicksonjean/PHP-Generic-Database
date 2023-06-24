<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/INI/pdo_mysql.ini')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/INI/pdo_pgsql.ini')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/INI/pdo_sqlsrv.ini')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/INI/pdo_oci.ini')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/INI/pdo_firebird.ini')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/INI/pdo_sqlite.ini')->connect();

var_dump($sqlite);
