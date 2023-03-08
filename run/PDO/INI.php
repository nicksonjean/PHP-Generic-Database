<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/INI/mysql.ini')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/INI/pgsql.ini')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/INI/sqlsrv.ini')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/INI/oci.ini')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/INI/firebird.ini')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/INI/sqlite.ini')->connect();

var_dump($sqlite);
