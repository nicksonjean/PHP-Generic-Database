<?php

use GenericDatabase\Engine\ODBCEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_mysql.xml')->connect();

var_dump($mysql);

$pgsql = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_pgsql.xml')->connect();

var_dump($pgsql);

$sqlsrv = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_sqlsrv.xml')->connect();

var_dump($sqlsrv);

$oci = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_oci.xml')->connect();

var_dump($oci);

$firebird = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_firebird.xml')->connect();

var_dump($firebird);

$sqlite = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_sqlite.xml')->connect();

var_dump($sqlite);

$access = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_access.xml')->connect();

var_dump($access);

$excel = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_excel.xml')->connect();

var_dump($excel);

$text = ODBCEngine::new(PATH_ROOT . '/resources/dsn/xml/odbc_text.xml')->connect();

var_dump($text);
