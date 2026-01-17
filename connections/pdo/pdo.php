<?php

$var = Autoloader::getLoadedVariables();

$i18n = $var['i18n'] ?? '';

$schema = [
    ['path' => 'pdo', 'files' => ['mysql'], 'vars' => ['label' => 'MySQL', 'extension' => 'pdo', 'env' => 'MYSQL', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo', 'files' => ['pgsql'], 'vars' => ['label' => 'PostgreSQL', 'extension' => 'pdo', 'env' => 'PGSQL', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo', 'files' => ['sqlsrv'], 'vars' => ['label' => 'SQL Server', 'extension' => 'pdo', 'env' => 'SQLSRV', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo', 'files' => ['oci'], 'vars' => ['label' => 'Oracle', 'extension' => 'pdo', 'env' => 'OCI', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo', 'files' => ['firebird'], 'vars' => ['label' => 'Firebird', 'extension' => 'pdo', 'env' => 'FBIRD', 'method' => 'pdo', 'lang' => $i18n]],
    ['path' => 'pdo', 'files' => ['sqlite'], 'vars' => ['label' => 'SQLite', 'extension' => 'pdo', 'env' => 'SQLITE', 'method' => 'pdo', 'lang' => $i18n]]
];

Autoloader::loadFromArray($schema);
