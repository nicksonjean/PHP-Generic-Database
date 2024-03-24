<?php

use GenericDatabase\Engine\ODBCEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_mysql.json')->connect();

var_dump($mysql);

$pgsql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_pgsql.json')->connect();

var_dump($pgsql);

$sqlsrv = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_sqlsrv.json')->connect();

var_dump($sqlsrv);

$oci = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_oci.json')->connect();

var_dump($oci);

$firebird = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_firebird.json')->connect();

var_dump($firebird);

$sqlite = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_sqlite.json')->connect();

var_dump($sqlite);

$access = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_access.json')->connect();

var_dump($access);

$excel = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_excel.json')->connect();

var_dump($excel);

$text = ODBCEngine::new(PATH_ROOT . '/resources/dsn/json/odbc_text.json')->connect();

var_dump($text);
