<?php

use GenericDatabase\Engine\ODBCConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_mysql.xml')->connect();

var_dump($mysql);

$pgsql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_pgsql.xml')->connect();

var_dump($pgsql);

$sqlsrv = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_sqlsrv.xml')->connect();

var_dump($sqlsrv);

$oci = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_oci.xml')->connect();

var_dump($oci);

$firebird = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_firebird.xml')->connect();

var_dump($firebird);

$sqlite = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_sqlite.xml')->connect();

var_dump($sqlite);

$access = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_access.xml')->connect();

var_dump($access);

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $excel = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_excel.xml')->connect();

    var_dump($excel);

    $text = ODBCConnection::new(PATH_ROOT . '/resources/dsn/xml/odbc_text.xml')->connect();

    var_dump($text);

}
