<?php

use GenericDatabase\Engine\ODBCConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_mysql.json')->connect();

var_dump($mysql);

$pgsql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_pgsql.json')->connect();

var_dump($pgsql);

$sqlsrv = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_sqlsrv.json')->connect();

var_dump($sqlsrv);

$oci = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_oci.json')->connect();

var_dump($oci);

$firebird = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_firebird.json')->connect();

var_dump($firebird);

$sqlite = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_sqlite.json')->connect();

var_dump($sqlite);

$access = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_access.json')->connect();

var_dump($access);

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $excel = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_excel.json')->connect();

    var_dump($excel);

    $text = ODBCConnection::new(PATH_ROOT . '/resources/dsn/json/odbc_text.json')->connect();

    var_dump($text);

}
