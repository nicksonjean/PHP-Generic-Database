<?php

$var = Autoloader::getLoadedVariables();

$i18n = $var['i18n'] ?? '';

$schema = [
    ['path' => 'native', 'files' => ['mysql'], 'vars' => ['label' => 'MySQL', 'extension' => 'mysqli', 'env' => 'MYSQL', 'method' => 'mysqli_connect', 'lang' => $i18n]],
    ['path' => 'native', 'files' => ['pgsql'], 'vars' => ['label' => 'PostgreSQL', 'extension' => 'pgsql', 'env' => 'PGSQL', 'method' => 'pg_connect', 'lang' => $i18n]],
    ['path' => 'native', 'files' => ['sqlsrv'], 'vars' => ['label' => 'SQL Server', 'extension' => 'sqlsrv', 'env' => 'SQLSRV', 'method' => 'sqlsrv_connect', 'lang' => $i18n]],
    ['path' => 'native', 'files' => ['oci'], 'vars' => ['label' => 'Oracle', 'extension' => 'oci8', 'env' => 'OCI', 'method' => 'oci_connect', 'lang' => $i18n]],
    ['path' => 'native', 'files' => ['firebird'], 'vars' => ['label' => 'Firebird', 'extension' => 'interbase', 'env' => 'FBIRD', 'method' => 'ibase_connect', 'lang' => $i18n]],
    ['path' => 'native', 'files' => ['sqlite'], 'vars' => ['label' => 'SQLite', 'extension' => 'sqlite3', 'env' => 'SQLITE', 'method' => 'sqlite3', 'lang' => $i18n]],
];

Autoloader::loadFromArray($schema);
