<?php

use GenericDatabase\Engine\ODBCEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_mysql.yaml')->connect();

var_dump($mysql);

$pgsql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_pgsql.yaml')->connect();

var_dump($pgsql);

$sqlsrv = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_sqlsrv.yaml')->connect();

var_dump($sqlsrv);

$oci = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_oci.yaml')->connect();

var_dump($oci);

$firebird = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_firebird.yaml')->connect();

var_dump($firebird);

$sqlite = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_sqlite.yaml')->connect();

var_dump($sqlite);

$access = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_access.yaml')->connect();

var_dump($access);

$excel = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_excel.yaml')->connect();

var_dump($excel);

$text = ODBCEngine::new(PATH_ROOT . '/resources/dsn/yaml/odbc_text.yaml')->connect();

var_dump($text);
