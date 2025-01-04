<?php

$var = getVars();

$lang = $var['lang'] ?? '';

$schema = [
    ['path' => 'pdo', 'files' => ['mysql'], 'vars' => ['label' => 'MySQL', 'extension' => 'pdo', 'env' => 'MYSQL', 'method' => 'pdo', 'lang' => $lang]],
    ['path' => 'pdo', 'files' => ['pgsql'], 'vars' => ['label' => 'PostgreSQL', 'extension' => 'pdo', 'env' => 'PGSQL', 'method' => 'pdo', 'lang' => $lang]],
    ['path' => 'pdo', 'files' => ['sqlsrv'], 'vars' => ['label' => 'SQL Server', 'extension' => 'pdo', 'env' => 'SQLSRV', 'method' => 'pdo', 'lang' => $lang]],
    ['path' => 'pdo', 'files' => ['oci'], 'vars' => ['label' => 'Oracle', 'extension' => 'pdo', 'env' => 'OCI', 'method' => 'pdo', 'lang' => $lang]],
    ['path' => 'pdo', 'files' => ['firebird'], 'vars' => ['label' => 'Firebird', 'extension' => 'pdo', 'env' => 'FBIRD', 'method' => 'pdo', 'lang' => $lang]],
    ['path' => 'pdo', 'files' => ['sqlite'], 'vars' => ['label' => 'SQLite', 'extension' => 'pdo', 'env' => 'SQLITE', 'method' => 'pdo', 'lang' => $lang]]
];

Autoloader::loadFromArray($schema);
