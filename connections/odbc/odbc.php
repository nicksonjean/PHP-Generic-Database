<?php

$var = Autoloader::getLoadedVariables();

$i18n = $var['i18n'] ?? '';

$schema = [
    ['path' => 'odbc', 'files' => ['mysql'], 'vars' => ['label' => 'MySQL', 'extension' => 'odbc', 'env' => 'MYSQL', 'method' => 'odbc_connect', 'lang' => $i18n]],
    ['path' => 'odbc', 'files' => ['pgsql'], 'vars' => ['label' => 'PostgreSQL', 'extension' => 'odbc', 'env' => 'PGSQL', 'method' => 'odbc_connect', 'lang' => $i18n]],
    ['path' => 'odbc', 'files' => ['sqlsrv'], 'vars' => ['label' => 'SQL Server', 'extension' => 'odbc', 'env' => 'SQLSRV', 'method' => 'odbc_connect', 'lang' => $i18n]],
    ['path' => 'odbc', 'files' => ['oci'], 'vars' => ['label' => 'Oracle', 'extension' => 'odbc', 'env' => 'OCI', 'method' => 'odbc_connect', 'lang' => $i18n]],
    ['path' => 'odbc', 'files' => ['firebird'], 'vars' => ['label' => 'Firebird', 'extension' => 'odbc', 'env' => 'FBIRD', 'method' => 'odbc_connect', 'lang' => $i18n]],
    ['path' => 'odbc', 'files' => ['sqlite'], 'vars' => ['label' => 'SQLite', 'extension' => 'odbc', 'env' => 'SQLITE', 'method' => 'odbc_connect', 'lang' => $i18n]],
    ['path' => 'odbc', 'files' => ['access'], 'vars' => ['label' => 'Access', 'extension' => 'odbc', 'env' => 'ACCESS', 'method' => 'odbc_connect', 'lang' => $i18n]],
];

Autoloader::loadFromArray($schema);
