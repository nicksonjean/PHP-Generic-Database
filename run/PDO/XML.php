<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/XML/mysql.xml')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/XML/pgsql.xml')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/XML/sqlsrv.xml')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/XML/oci.xml')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/XML/firebird.xml')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/XML/sqlite.xml')->connect();

var_dump($sqlite);
