<?php

use GenericDatabase\Engine\ODBCEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_mysql.ini')->connect();

var_dump($mysql);

$pgsql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_pgsql.ini')->connect();

var_dump($pgsql);

$sqlsrv = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_sqlsrv.ini')->connect();

var_dump($sqlsrv);

$oci = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_oci.ini')->connect();

var_dump($oci);

$firebird = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_firebird.ini')->connect();

var_dump($firebird);

$sqlite = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_sqlite.ini')->connect();

var_dump($sqlite);

$access = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_access.ini')->connect();

var_dump($access);

$excel = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_excel.ini')->connect();

var_dump($excel);

$text = ODBCEngine::new(PATH_ROOT . '/resources/dsn/ini/odbc_text.ini')->connect();

var_dump($text);
