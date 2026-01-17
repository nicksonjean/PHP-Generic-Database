<?php

$var = Autoloader::getLoadedVariables();

$i18n = $var['i18n'] ?? '';

$schema = [
    ['path' => 'pdo_odbc', 'files' => ['mysql'], 'vars' => ['label' => 'MySQL', 'extensions' => ['pdo', 'odbc'], 'env' => 'MYSQL', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo_odbc', 'files' => ['pgsql'], 'vars' => ['label' => 'PostgreSQL', 'extensions' => ['pdo', 'odbc'], 'env' => 'PGSQL', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo_odbc', 'files' => ['sqlsrv'], 'vars' => ['label' => 'SQL Server', 'extensions' => ['pdo', 'odbc'], 'env' => 'SQLSRV', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo_odbc', 'files' => ['oci'], 'vars' => ['label' => 'Oracle', 'extensions' => ['pdo', 'odbc'], 'env' => 'OCI', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo_odbc', 'files' => ['firebird'], 'vars' => ['label' => 'Firebird', 'extensions' => ['pdo', 'odbc'], 'env' => 'FBIRD', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo_odbc', 'files' => ['sqlite'], 'vars' => ['label' => 'SQLite', 'extensions' => ['pdo', 'odbc'], 'env' => 'SQLITE', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo_odbc', 'files' => ['access'], 'vars' => ['label' => 'Access', 'extensions' => ['pdo', 'odbc'], 'env' => 'ACCESS', 'method' => 'pdo', 'lang' => $i18n]],
];

Autoloader::loadFromArray($schema);
