<?php
/**
 * Conexão: custom_fluent_odbc_oci
 * Gerado pelo Assistente de Conexão - PHP Generic Database
 */
declare(strict_types=1);

use Dotenv\Dotenv;
use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Engine\ODBCQueryBuilder;

if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', dirname(__DIR__, 3));
}

require_once PATH_ROOT . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.custom_fluent_odbc_oci')) {
    Dotenv::createImmutable(__DIR__, '.custom_fluent_odbc_oci')->load();
} elseif (file_exists(PATH_ROOT . '/.env')) {
    Dotenv::createImmutable(PATH_ROOT)->load();
}

$classMap = [
    'ODBC' => 'GenericDatabase\Engine\ODBC\Connection\ODBC',
    'PDO' => 'PDO',
    'MySQL' => 'GenericDatabase\Engine\MySQLi\Connection\MySQL',
    'PgSQL' => 'GenericDatabase\Engine\PgSQL\Connection\PgSQL',
    'SQLSrv' => 'GenericDatabase\Engine\SQLSrv\Connection\SQLSrv',
    'OCI' => 'GenericDatabase\Engine\OCI\Connection\OCI',
    'Firebird' => 'GenericDatabase\Engine\Firebird\Connection\Firebird',
    'SQLite' => 'GenericDatabase\Engine\SQLite\Connection\SQLite',
    'JSON' => 'GenericDatabase\Engine\JSON\Connection\JSON',
    'CSV' => 'GenericDatabase\Engine\CSV\Connection\CSV',
    'INI' => 'GenericDatabase\Engine\INI\Connection\INI',
    'XML' => 'GenericDatabase\Engine\XML\Connection\XML',
    'YAML' => 'GenericDatabase\Engine\YAML\Connection\YAML',
    'NEON' => 'GenericDatabase\Engine\NEON\Connection\NEON',
];
$opts = json_decode($_ENV['OCI_OPTIONS'] ?? '[]', true);
$options = [];
foreach ($opts as $k => $v) {
    $rk = $k;
    if (preg_match('/^(\\w+)::(\\w+)$/', $k, $m) && isset($classMap[$m[1]])) {
        $rk = $classMap[$m[1]] . '::' . $m[2];
    }
    $rv = $v;
    if (is_string($v) && preg_match('/^(\\w+)::(\\w+)$/', $v, $m2) && isset($classMap[$m2[1]])) {
        $rv = $classMap[$m2[1]] . '::' . $m2[2];
    }
    $options[defined($rk) ? constant($rk) : $k] = is_string($rv) && defined($rv) ? constant($rv) : $v;
}

$context = ODBCConnection::setDriver('oci')
    ::setHost($_ENV['OCI_HOST'])
    ::setPort((int) $_ENV['OCI_PORT'])
    ::setDatabase($_ENV['OCI_DATABASE'])
    ::setUser($_ENV['OCI_USERNAME'])
    ::setPassword($_ENV['OCI_PASSWORD'])
    ::setCharset($_ENV['OCI_CHARSET'])
    ::setOptions($options)
    ::setException(true);

$context = $context->connect();

// Exemplo: $qb = ODBCQueryBuilder::with($context);
