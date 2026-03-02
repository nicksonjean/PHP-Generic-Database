<?php
/**
 * Conexão Preset: Chainable / Pdo / Pgsql
 * Gerado pelo Assistente de Conexão - PHP Generic Database
 */
declare(strict_types=1);

use Dotenv\Dotenv;
use GenericDatabase\Modules\Chainable;

if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', dirname(__DIR__, 3));
}

require_once PATH_ROOT . '/vendor/autoload.php';

if (file_exists(PATH_ROOT . '/.env')) {
    Dotenv::createImmutable(PATH_ROOT)->load();
}

$env = [
    'host' => ($_ENV['PGSQL_HOST'] ?? ''),
    'port' => (int) ($_ENV['PGSQL_PORT'] ?? ''),
    'database' => ($_ENV['PGSQL_DATABASE'] ?? ''),
    'user' => ($_ENV['PGSQL_USERNAME'] ?? ''),
    'password' => ($_ENV['PGSQL_PASSWORD'] ?? ''),
    'charset' => ($_ENV['PGSQL_CHARSET'] ?? ''),
];

$context = Chainable::pdoPgSQL($env, false, false);

$context = $context->connect();

// Exemplo: $qb = PDOQueryBuilder::with($context);
