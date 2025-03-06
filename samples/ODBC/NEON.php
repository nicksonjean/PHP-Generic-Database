<?php

use GenericDatabase\Engine\ODBCConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_mysql.neon')->connect();

var_dump($mysql);

$pgsql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_pgsql.neon')->connect();

var_dump($pgsql);

$sqlsrv = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_sqlsrv.neon')->connect();

var_dump($sqlsrv);

$oci = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_oci.neon')->connect();

var_dump($oci);

$firebird = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_firebird.neon')->connect();

var_dump($firebird);

$sqlite = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_sqlite.neon')->connect();

var_dump($sqlite);

$access = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_access.neon')->connect();

var_dump($access);

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $excel = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_excel.neon')->connect();

    var_dump($excel);

    $text = ODBCConnection::new(PATH_ROOT . '/resources/dsn/neon/odbc_text.neon')->connect();

    var_dump($text);
}
