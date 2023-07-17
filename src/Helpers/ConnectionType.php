<?php

namespace GenericDatabase\Helpers;

enum ConnectionType: string
{
    case NAT_OCI = 'oci8 connection';
    case NAT_OCI_PERSISTENT = 'oci8 persistent connection';
    case NAT_SQLSRV_PERSISTENT = 'SQL Server Connection';
    case NAT_MYSQL = 'mysql link';
    case NAT_PGSQL = 'PgSql\Connection';
    case NAT_MSSQL = 'mssql link';
    case NAT_FBIRD = 'Firebird link';
    case NAT_IBASE = 'InterBase link';
    case NAT_FBIRD_PERSISTENT = 'Firebird persistent link';
    case NAT_IBASE_PERSISTENT = 'InterBase persistent link';
    case NAT_FBIRD_IBASE = 'Firebird/InterBase link';
    case NAT_FBIRD_IBASE_PERSISTENT = 'Firebird/InterBase persistent link';
    case NAT_SYBASE = 'sybase link';
    case NAT_MYSQLI = 'mysqli';
    case NAT_SQLITE = 'SQLite3';
    case PDO_MYSQL = 'mysql';
    case PDO_PGSQL = 'pgsql';
    case PDO_SQLITE = 'sqlite';
    case PDO_SQLSRV = 'sqlsrv';
    case PDO_OCI = 'oci';
    case PDO_FBIRD = 'firebird';
    case PDO_IBM = 'ibm';
    case PDO_INFORMIX = 'informix';
    case PDO_MSSQL = 'mssql';
    case PDO_DBLIB = 'dblib';
    case PDO_ODBC = 'odbc';
}
