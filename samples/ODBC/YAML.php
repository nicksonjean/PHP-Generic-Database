<?php

use GenericDatabase\Engine\ODBCConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$mysql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_mysql.yaml')->connect();

var_dump($mysql);

$pgsql = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_pgsql.yaml')->connect();

var_dump($pgsql);

$sqlsrv = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_sqlsrv.yaml')->connect();

var_dump($sqlsrv);

$oci = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_oci.yaml')->connect();

var_dump($oci);

$firebird = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_firebird.yaml')->connect();

var_dump($firebird);

$sqlite = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_sqlite.yaml')->connect();

var_dump($sqlite);

$access = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_access.yaml')->connect();

var_dump($access);

if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    $excel = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_excel.yaml')->connect();

    var_dump($excel);

    $text = ODBCConnection::new(PATH_ROOT . '/resources/dsn/yaml/odbc_text.yaml')->connect();

    var_dump($text);

}
