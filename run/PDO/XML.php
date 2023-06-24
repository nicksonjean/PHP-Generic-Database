<?php

use GenericDatabase\Engine\PDOEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = PDOEngine::new('../../assets/XML/pdo_mysql.xml')->connect();

var_dump($mysql);

$pgsql = PDOEngine::new('../../assets/XML/pdo_pgsql.xml')->connect();

var_dump($pgsql);

$sqlsrv = PDOEngine::new('../../assets/XML/pdo_sqlsrv.xml')->connect();

var_dump($sqlsrv);

$oci = PDOEngine::new('../../assets/XML/pdo_oci.xml')->connect();

var_dump($oci);

$firebird = PDOEngine::new('../../assets/XML/pdo_firebird.xml')->connect();

var_dump($firebird);

$sqlite = PDOEngine::new('../../assets/XML/pdo_sqlite.xml')->connect();

var_dump($sqlite);
