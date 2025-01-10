<?php

use GenericDatabase\Engine\ODBCConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_mysql.ini')->connect();

var_dump($mysql);

$pgsql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_pgsql.ini')->connect();

var_dump($pgsql);

$sqlsrv = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_sqlsrv.ini')->connect();

var_dump($sqlsrv);

$oci = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_oci.ini')->connect();

var_dump($oci);

$firebird = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_firebird.ini')->connect();

var_dump($firebird);

$sqlite = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_sqlite.ini')->connect();

var_dump($sqlite);

$access = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_access.ini')->connect();

var_dump($access);

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $excel = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_excel.ini')->connect();

    var_dump($excel);

    $text = ODBCConnection::new(PATH_ROOT . '/resources/dsn/ini/odbc_text.ini')->connect();

    var_dump($text);

}
