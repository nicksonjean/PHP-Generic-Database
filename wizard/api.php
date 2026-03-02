<?php

/**
 * API do Assistente de Conexão
 * Endpoints: test_connection, complete_connection
 */

declare(strict_types=1);

ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/** @var string Caminho raiz do projeto (pasta pai do wizard) */
if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', dirname(__DIR__));
}
require_once PATH_ROOT . '/vendor/autoload.php';
require_once __DIR__ . '/includes/Functions.php';

// ── Wizard constants ───────────────────────────────────────────────────────────
// WIZARD_DATA_DIR and WIZARD_SETTINGS_FILE are defined explicitly so that static
// analysers can resolve them. All other constants are derived from settings.toml.
if (!defined('WIZARD_DATA_DIR')) {
    define('WIZARD_DATA_DIR', __DIR__ . '/data');
}
if (!defined('WIZARD_SETTINGS_FILE')) {
    define('WIZARD_SETTINGS_FILE', WIZARD_DATA_DIR . '/settings.toml');
}
foreach (read_toml_file(WIZARD_SETTINGS_FILE) as $_wizKey => $_wizVal) {
    $_wizConst = 'WIZARD_' . strtoupper($_wizKey);
    if (!defined($_wizConst)) {
        define($_wizConst, is_string($_wizVal) && str_starts_with($_wizVal, '/') ? WIZARD_DATA_DIR . $_wizVal : $_wizVal);
    }
}
unset($_wizKey, $_wizVal, $_wizConst);
// ── End Wizard constants ──────────────────────────────────────────────────────

// Carregamento dinâmico: .env é carregado sob demanda conforme active_profile.json (sem .var)

// Mapeamento Engine -> Drivers
$ENGINE_DRIVERS = [
    'native' => ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite'],
    'pdo' => ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite'],
    'odbc' => ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite', 'access', 'excel', 'text'],
    'pdo_odbc' => ['mysql', 'pgsql', 'sqlsrv', 'oci', 'firebird', 'sqlite', 'access', 'excel', 'text'],
    'flat_files' => ['csv', 'ini', 'json', 'neon', 'xml', 'yaml'],
];

// Mapeamento Driver -> Método Chainable/Fluent/Static Args/Static Array (e.g. MySQLi -> nativeMySQLi, PDO -> pdoMySQL, ODBC -> odbcMySQL, Flat Files -> nativeCSV)
$DRIVER_METHODS = [
    'mysql' => ['native' => 'nativeMySQLi', 'pdo' => 'pdoMySQL', 'odbc' => 'odbcMySQL', 'pdo_odbc' => 'odbcMySQL'],
    'pgsql' => ['native' => 'nativePgSQL', 'pdo' => 'pdoPgSQL', 'odbc' => 'odbcPgSQL', 'pdo_odbc' => 'odbcPgSQL'],
    'sqlsrv' => ['native' => 'nativeSQLSrv', 'pdo' => 'pdoSQLSrv', 'odbc' => 'odbcSQLSrv', 'pdo_odbc' => 'odbcSQLSrv'],
    'oci' => ['native' => 'nativeOCI', 'pdo' => 'pdoOCI', 'odbc' => 'odbcOCI', 'pdo_odbc' => 'odbcOCI'],
    'firebird' => ['native' => 'nativeFirebird', 'pdo' => 'pdoFirebird', 'odbc' => 'odbcFirebird', 'pdo_odbc' => 'odbcFirebird'],
    'sqlite' => ['native' => 'nativeSQLite', 'pdo' => 'pdoSQLite', 'odbc' => 'odbcSQLite', 'pdo_odbc' => 'odbcSQLite'],
    'access' => ['odbc' => 'odbcAccess', 'pdo_odbc' => 'odbcAccess'],
    'excel' => ['odbc' => 'odbcExcel', 'pdo_odbc' => 'odbcExcel'],
    'text' => ['odbc' => 'odbcText', 'pdo_odbc' => 'odbcText'],
    'csv' => ['flat_files' => 'nativeCSV'],
    'ini' => ['flat_files' => 'nativeINI'],
    'json' => ['flat_files' => 'nativeJSON'],
    'neon' => ['flat_files' => 'nativeNEON'],
    'xml' => ['flat_files' => 'nativeXML'],
    'yaml' => ['flat_files' => 'nativeYAML'],
];

// Mapeamento Driver -> Variáveis de ambiente
$DRIVER_ENV_KEYS = [
    'mysql' => ['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD', 'CHARSET'],
    'pgsql' => ['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD', 'CHARSET'],
    'sqlsrv' => ['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD', 'CHARSET'],
    'oci' => ['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD', 'CHARSET'],
    'firebird' => ['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD', 'CHARSET'],
    'sqlite' => ['DATABASE', 'CHARSET'],
    'access' => ['DATABASE', 'USERNAME', 'PASSWORD', 'CHARSET'],
    'excel' => ['DATABASE', 'CHARSET'],
    'text' => ['DATABASE', 'CHARSET'],
    'csv' => ['DATABASE', 'CHARSET'],
    'ini' => ['DATABASE', 'CHARSET'],
    'json' => ['DATABASE', 'CHARSET'],
    'neon' => ['DATABASE', 'CHARSET'],
    'xml' => ['DATABASE', 'CHARSET'],
    'yaml' => ['DATABASE', 'CHARSET'],
];

// Labels para persistência em profiles.json e active_profile.json (Engine, Driver, Instance Type)
$ENGINE_LABELS = [
    'native' => 'Native',
    'pdo' => 'PDO',
    'odbc' => 'ODBC',
    'pdo_odbc' => 'PDO + ODBC',
    'flat_files' => 'Flat Files',
];
$DRIVER_LABELS = [
    'mysql' => ['native' => 'MySQLi', 'default' => 'MySQL'],
    'pgsql' => 'PostgreSQL',
    'sqlsrv' => 'SQL Server',
    'oci' => 'Oracle',
    'firebird' => 'Firebird',
    'sqlite' => 'SQLite',
    'access' => 'Access',
    'excel' => 'Excel',
    'text' => 'Text',
    'csv' => 'CSV',
    'ini' => 'INI',
    'json' => 'JSON',
    'neon' => 'NEON',
    'xml' => 'XML',
    'yaml' => 'YAML',
];
$INSTANCE_TYPE_LABELS = ['specific' => 'Specific', 'strategy' => 'Strategy'];

function toEngineLabel(string $engine): string
{
    global $ENGINE_LABELS;
    $e = strtolower($engine);
    return $ENGINE_LABELS[$e] ?? ucfirst($e);
}

function toDriverLabel(string $driver, string $engine): string
{
    global $DRIVER_LABELS;
    $d = strtolower($driver);
    $e = strtolower($engine);
    $lbl = $DRIVER_LABELS[$d] ?? ucfirst($d);
    return is_array($lbl) ? ($e === 'native' ? $lbl['native'] : $lbl['default']) : $lbl;
}

function toInstanceTypeLabel(string $type): string
{
    global $INSTANCE_TYPE_LABELS;
    $t = strtolower($type);
    return $INSTANCE_TYPE_LABELS[$t] ?? ucfirst($t);
}

function fromEngineLabel(string $label): string
{
    $k = strtolower(preg_replace('/\s+/', '', trim($label)));
    $map = ['native' => 'native', 'pdo' => 'pdo', 'odbc' => 'odbc', 'pdo+odbc' => 'pdo_odbc', 'flatfiles' => 'flat_files'];
    return $map[$k] ?? str_replace(' ', '_', strtolower(trim($label)));
}

function fromDriverLabel(string $label, ?string $engine = null): string
{
    $l = strtolower(trim($label));
    if ($l === 'mysqli') return 'mysql';
    $map = ['mysql' => 'mysql', 'postgresql' => 'pgsql', 'sql server' => 'sqlsrv', 'oracle' => 'oci', 'firebird' => 'firebird', 'sqlite' => 'sqlite', 'access' => 'access', 'excel' => 'excel', 'text' => 'text', 'csv' => 'csv', 'ini' => 'ini', 'json' => 'json', 'neon' => 'neon', 'xml' => 'xml', 'yaml' => 'yaml'];
    return $map[$l] ?? str_replace(' ', '_', $l);
}

function fromInstanceTypeLabel(string $label): string
{
    $t = strtolower(trim($label));
    return ($t === 'strategy') ? 'strategy' : 'specific';
}

$DRIVER_ENV_PREFIX = [
    'mysql' => 'MYSQL',
    'pgsql' => 'PGSQL',
    'sqlsrv' => 'SQLSRV',
    'oci' => 'OCI',
    'firebird' => 'FBIRD',
    'sqlite' => 'SQLITE',
    'access' => 'ACCESS',
    'excel' => 'EXCEL',
    'text' => 'TEXT',
    'csv' => 'CSV',
    'ini' => 'INI',
    'json' => 'JSON',
    'neon' => 'NEON',
    'xml' => 'XML',
    'yaml' => 'YAML',
];

$OPTIONS_CLASS_MAP = [
    'ODBC' => 'GenericDatabase\\Engine\\ODBC\\Connection\\ODBC',
    'PDO' => 'PDO',
    'MySQL' => 'GenericDatabase\\Engine\\MySQLi\\Connection\\MySQL',
    'PgSQL' => 'GenericDatabase\\Engine\\PgSQL\\Connection\\PgSQL',
    'SQLSrv' => 'GenericDatabase\\Engine\\SQLSrv\\Connection\\SQLSrv',
    'OCI' => 'GenericDatabase\\Engine\\OCI\\Connection\\OCI',
    'Firebird' => 'GenericDatabase\\Engine\\Firebird\\Connection\\Firebird',
    'SQLite' => 'GenericDatabase\\Engine\\SQLite\\Connection\\SQLite',
    'JSON' => 'GenericDatabase\\Engine\\JSON\\Connection\\JSON',
    'CSV' => 'GenericDatabase\\Engine\\CSV\\Connection\\CSV',
    'INI' => 'GenericDatabase\\Engine\\INI\\Connection\\INI',
    'XML' => 'GenericDatabase\\Engine\\XML\\Connection\\XML',
    'YAML' => 'GenericDatabase\\Engine\\YAML\\Connection\\YAML',
    'NEON' => 'GenericDatabase\\Engine\\NEON\\Connection\\NEON',
];

/** Get the connection class FQN for a given engine/driver */
function getConnectionClass(string $engine, string $driver): string
{
    $map = [
        'native' => [
            'mysql' => 'GenericDatabase\\Engine\\MySQLiConnection',
            'pgsql' => 'GenericDatabase\\Engine\\PgSQLConnection',
            'sqlsrv' => 'GenericDatabase\\Engine\\SQLSrvConnection',
            'oci' => 'GenericDatabase\\Engine\\OCIConnection',
            'firebird' => 'GenericDatabase\\Engine\\FirebirdConnection',
            'sqlite' => 'GenericDatabase\\Engine\\SQLiteConnection',
        ],
        'pdo' => 'GenericDatabase\\Engine\\PDOConnection',
        'odbc' => 'GenericDatabase\\Engine\\ODBCConnection',
        'pdo_odbc' => 'GenericDatabase\\Engine\\ODBCConnection',
        'flat_files' => [
            'csv' => 'GenericDatabase\\Engine\\CSVConnection',
            'ini' => 'GenericDatabase\\Engine\\INIConnection',
            'json' => 'GenericDatabase\\Engine\\JSONConnection',
            'neon' => 'GenericDatabase\\Engine\\NEONConnection',
            'xml' => 'GenericDatabase\\Engine\\XMLConnection',
            'yaml' => 'GenericDatabase\\Engine\\YAMLConnection',
        ],
    ];
    $entry = $map[$engine] ?? null;
    if (is_array($entry)) {
        return $entry[$driver] ?? '';
    }
    return is_string($entry) ? $entry : '';
}

/** Get the engine identifier for the Strategy pattern (\Connection::setEngine($id)) */
function getStrategyEngineId(string $engine, string $driver): string
{
    $map = [
        'native' => [
            'mysql' => 'mysqli',
            'pgsql' => 'pgsql',
            'sqlsrv' => 'sqlsrv',
            'oci' => 'oci',
            'firebird' => 'firebird',
            'sqlite' => 'sqlite',
        ],
        'pdo' => 'pdo',
        'odbc' => 'odbc',
        'pdo_odbc' => 'odbc',
        'flat_files' => [
            'csv' => 'csv',
            'ini' => 'ini',
            'json' => 'json',
            'neon' => 'neon',
            'xml' => 'xml',
            'yaml' => 'yaml',
        ],
    ];
    $entry = $map[$engine] ?? '';
    if (is_array($entry)) {
        return $entry[$driver] ?? '';
    }
    return is_string($entry) ? $entry : '';
}

/** Does this engine require setDriver() on the connection? */
function needsSetDriver(string $engine): bool
{
    return in_array($engine, ['pdo', 'odbc', 'pdo_odbc'], true);
}

/**
 * Build a connection instance directly (without module defaults) for test/save.
 */
function buildConnectionInstance(string $engine, string $driver, array $env, bool $strategy): mixed
{
    global $DRIVER_ENV_PREFIX, $DRIVER_ENV_KEYS;

    $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
    $keys = $DRIVER_ENV_KEYS[$driver] ?? [];

    if ($strategy) {
        $connClass = 'GenericDatabase\\Connection';
        $instance = new $connClass();
        $instance->setEngine(getStrategyEngineId($engine, $driver));
    } else {
        $connClass = getConnectionClass($engine, $driver);
        if (!$connClass || !class_exists($connClass)) {
            throw new \Exception("Classe de conexão não encontrada para $engine/$driver");
        }
        $instance = new $connClass();
    }

    if (needsSetDriver($engine)) {
        $instance->setDriver($driver);
    }

    foreach ($keys as $key) {
        $envKey = "{$prefix}_{$key}";
        $value = $env[$envKey] ?? '';
        match ($key) {
            'HOST' => $instance->setHost($value),
            'PORT' => $instance->setPort((int) $value),
            'DATABASE' => $instance->setDatabase($value),
            'USERNAME' => $instance->setUser($value),
            'PASSWORD' => $instance->setPassword($value),
            'CHARSET' => $instance->setCharset($value),
        };
    }

    $instance->setOptions([]);
    $instance->setException(true);
    return $instance;
}

/**
 * Parse options text to array with STRING keys (e.g. "\PDO::ATTR_PERSISTENT") for JSON storage.
 * Supports two formats:
 * 1. JSON: {"\PDO::ATTR_PERSISTENT":true,"\PDO::ATTR_EMULATE_PREPARES":true,...}
 * 2. Line format: key => value or key -> value (one per line), keys may have optional quotes
 */
function parseOptions(string $optionsText, string $engine): array
{
    global $OPTIONS_CLASS_MAP;
    $text = trim($optionsText);
    if ($text === '') {
        return [];
    }

    $result = [];

    if (preg_match('/^\s*\{/', $text)) {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            if (isset($decoded['options']) && is_array($decoded['options'])) {
                $opts = $decoded['options'][0] ?? $decoded['options'];
                if (is_array($opts)) {
                    $decoded = $opts;
                }
            }
            foreach ($decoded as $k => $v) {
                $key = is_string($k) ? trim(preg_replace('/\s+/', '', $k)) : (string) $k;
                $key = normalizeOptionKey($key);
                if ($key !== null) {
                    $result[$key] = $v;
                }
            }
            return $result;
        }
    }

    $lines = preg_split('/\r\n|\r|\n/', $text);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '//') === 0 || strpos($line, '#') === 0) {
            continue;
        }
        // Support both => and -> as separator
        if (!preg_match('/^(.+?)\s*(?:=>|->)\s*(.+)$/s', $line, $m)) {
            continue;
        }

        $keyExpr = trim($m[1]);
        $valExpr = trim($m[2]);
        if (strlen($keyExpr) >= 2 && ($keyExpr[0] === '"' || $keyExpr[0] === "'") && $keyExpr[0] === $keyExpr[strlen($keyExpr) - 1]) {
            $keyExpr = substr($keyExpr, 1, -1);
        }
        $keyExpr = preg_replace('/\s+/', '', $keyExpr);
        $keyExpr = normalizeOptionKey($keyExpr);
        if ($keyExpr === null) {
            continue;
        }

        $val = resolveValue($valExpr, $OPTIONS_CLASS_MAP, false);
        $result[$keyExpr] = $val;
    }

    return $result;
}

/** Normalize option key (e.g. \MYSQL::X -> \MySQL::X) and return canonical form or null if invalid */
function normalizeOptionKey(string $key): ?string
{
    $key = trim(preg_replace('/\s+/', '', $key));
    if (preg_match('/^["\']?(\w+)::(\w+)["\']?$/', $key, $m)) {
        $prefix = $m[1];
        $suffix = $m[2];
        $alias = ['MYSQL' => 'MySQL', 'PDO' => 'PDO', 'ODBC' => 'ODBC', 'PGSQL' => 'PgSQL', 'SQLSRV' => 'SQLSrv', 'OCI' => 'OCI', 'FBIRD' => 'Firebird', 'FIREBIRD' => 'Firebird', 'SQLITE' => 'SQLite'];
        $canonical = ($alias[strtoupper($prefix)] ?? $prefix) . '::' . $suffix;
        return preg_match('/^\w+::\w+$/', $canonical) ? $canonical : null;
    }
    return preg_match('/^\w+::\w+$/', $key) ? $key : null;
}

/**
 * Convert options with string keys to numeric keys for setOptions (library expects int keys).
 * Resolves constant references (e.g. "\MySQL::FETCH_OBJ") to numeric values.
 * Converts numeric values to int/float as needed - the Test button validates the connection.
 */
function optionsForConnection(array $options, string $engine): array
{
    global $OPTIONS_CLASS_MAP;
    $numeric = [];
    foreach ($options as $keyStr => $val) {
        $k = resolveConstant((string) $keyStr, $OPTIONS_CLASS_MAP);
        if ($k !== null) {
            $resolvedVal = is_string($val) ? resolveValue($val, $OPTIONS_CLASS_MAP) : $val;
            // Cast numeric values to proper type (avoids "string * int" etc.) - non-numeric strings stay as-is
            if (is_numeric($resolvedVal)) {
                $resolvedVal = strpos((string) $resolvedVal, '.') !== false ? (float) $resolvedVal : (int) $resolvedVal;
            }
            $numeric[$k] = $resolvedVal;
        }
    }
    return $numeric;
}

/**
 * Generate PHP source code for options array using constant references.
 * Converts string-keyed options (e.g. "\MySQL::ATTR_PERSISTENT" => true)
 * to PHP code: [\MySQL::ATTR_PERSISTENT => true, \MySQL::ATTR_DEFAULT_FETCH_MODE => \MySQL::FETCH_OBJ]
 * Returns ['code' => string, 'uses' => string[]] with the PHP code and required use statements.
 */
function optionsToPhpCode(array $options): array
{
    global $OPTIONS_CLASS_MAP;
    if (empty($options)) {
        return ['code' => '[]', 'uses' => []];
    }

    $prefixes = [];
    $lines = [];
    foreach ($options as $key => $val) {
        // Key: "MySQL::ATTR_PERSISTENT" → MySQL::ATTR_PERSISTENT
        if (preg_match('/^(\w+)::(\w+)$/', $key, $m)) {
            $prefixes[$m[1]] = true;
        }

        // Value: detect constant references like "MySQL::FETCH_OBJ"
        $valPhp = formatPhpValue($val, $prefixes);
        $lines[] = "    $key => $valPhp,";
    }

    // Collect use statements for all class prefixes found
    $uses = [];
    foreach (array_keys($prefixes) as $prefix) {
        $fqcn = $OPTIONS_CLASS_MAP[$prefix] ?? null;
        if ($fqcn && $fqcn !== $prefix) {
            $uses[] = $fqcn;
        }
    }

    $code = "[\n" . implode("\n", $lines) . "\n]";
    return ['code' => $code, 'uses' => $uses];
}

/** Format a PHP value for code generation, detecting constant references */
function formatPhpValue(mixed $val, array &$prefixes): string
{
    if (is_bool($val)) {
        return $val ? 'true' : 'false';
    }
    if (is_int($val) || is_float($val)) {
        return (string) $val;
    }
    if (is_string($val)) {
        // Check if value is a constant reference like "MySQL::FETCH_OBJ"
        // or a bitwise OR expression like "MySQL::REPORT_ERROR | MySQL::REPORT_STRICT"
        $parts = preg_split('/\s*\|\s*/', $val);
        $allConstants = true;
        foreach ($parts as $part) {
            if (!preg_match('/^(\w+)::(\w+)$/', trim($part))) {
                $allConstants = false;
                break;
            }
        }
        if ($allConstants && count($parts) > 0) {
            // Collect prefixes from value constants too
            foreach ($parts as $part) {
                if (preg_match('/^(\w+)::/', trim($part), $m)) {
                    $prefixes[$m[1]] = true;
                }
            }
            return implode(' | ', array_map('trim', $parts));
        }
        // Regular string value - escape for PHP
        $escaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $val);
        return "'$escaped'";
    }
    return var_export($val, true);
}

/**
 * Normalize options for display: convert numeric keys to string constant names (legacy format).
 */
function normalizeOptionsForDisplay(array $options, string $engine): array
{
    global $OPTIONS_CLASS_MAP;
    $classMap = $OPTIONS_CLASS_MAP;
    $reverseMap = [];
    foreach (['PDO', 'MySQL', 'ODBC', 'PgSQL', 'SQLSrv', 'OCI', 'Firebird', 'SQLite'] as $prefix) {
        $class = $classMap[$prefix] ?? null;
        if ($class && class_exists($class)) {
            try {
                $constants = (new \ReflectionClass($class))->getConstants();
                foreach ($constants as $name => $value) {
                    if (is_int($value)) {
                        $reverseMap[$value] = $prefix . '::' . $name;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
    $out = [];
    foreach ($options as $k => $v) {
        $key = is_numeric($k) ? ($reverseMap[(int) $k] ?? $k) : $k;
        $out[$key] = $v;
    }
    return $out;
}

function resolveConstant(string $expr, array $classMap): ?int
{
    $expr = trim($expr);
    if (is_numeric($expr)) {
        return (int) $expr;
    }
    if (preg_match('/^(\w+)::(\w+)$/', $expr, $m)) {
        $class = $classMap[$m[1]] ?? null;
        if ($class && defined("$class::{$m[2]}")) {
            return constant("$class::{$m[2]}");
        }
    }
    return null;
}

function resolveValue(mixed $expr, array $classMap, bool $resolveConstants = true): mixed
{
    $expr = trim((string) $expr);
    // Strip only a single matched pair of outer quotes (preserve inner quotes like SET NAMES 'utf8')
    if (strlen($expr) >= 2) {
        $first = $expr[0];
        $last = $expr[strlen($expr) - 1];
        if (($first === '"' || $first === "'") && $first === $last) {
            $expr = substr($expr, 1, -1);
        }
    }
    if (is_numeric($expr)) {
        return strpos($expr, '.') !== false ? (float)$expr : (int)$expr;
    }
    if (strtolower($expr) === 'true') {
        return true;
    }
    if (strtolower($expr) === 'false') {
        return false;
    }
    // Support: ODBC::REPORT_ERROR | ODBC::REPORT_STRICT, MySQL::FETCH_OBJ (allow spaces around ::)
    if ($resolveConstants) {
        $parts = preg_split('/\s*\|\s*/', $expr);
        $result = null;
        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^(\w+)\s*::\s*(\w+)$/', $part, $m)) {
                $v = resolveConstant($m[1] . '::' . $m[2], $classMap);
                if ($v !== null) {
                    $result = $result === null ? $v : ($result | $v);
                }
            }
        }
        if ($result !== null) {
            return $result;
        }
    }
    // Normalize SQL-like strings: replace Unicode/smart quotes with ASCII (avoids syntax errors)
    if (is_string($expr) && (stripos($expr, 'SET ') === 0 || stripos($expr, 'SELECT ') === 0)) {
        $expr = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}"], ["'", "'", '"', '"'], $expr);
    }
    return $expr;
}

/**
 * Parse .env file and return key=>value array (does not modify $_ENV).
 */
function parseEnvFile(string $filePath): array
{
    if (!file_exists($filePath)) {
        return [];
    }
    $content = file_get_contents($filePath);
    $result = [];
    $lines = preg_split('/\r\n|\r|\n/', $content);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        $pos = strpos($line, '=');
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
            $value = str_replace(['\\"', "\\'"], ['"', "'"], $value);
        }
        $result[$key] = $value;
    }
    return $result;
}

/**
 * Load env from connection's .env file (wizard/data/profiles/.{name})
 * Returns array with PREFIX_KEY => value (does not modify $_ENV).
 */
function loadEnvFromConnection(string $connectionName): array
{
    $profilesDir = defined('WIZARD_PROFILES_DIR') ? WIZARD_PROFILES_DIR : __DIR__ . '/data/profiles';
    $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $connectionName);
    $envFile = $profilesDir . '/.' . $safeName;
    return parseEnvFile($envFile);
}

/**
 * Build connection_data for form from env vars (PREFIX_HOST, PREFIX_OPTIONS, etc.)
 */
function buildConnectionDataFromEnv(array $env, string $driver): array
{
    global $DRIVER_ENV_KEYS, $DRIVER_ENV_PREFIX;
    $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
    $keys = $DRIVER_ENV_KEYS[$driver] ?? [];
    $conn = [];
    foreach ($keys as $key) {
        $envKey = "{$prefix}_{$key}";
        $storageKey = ($key === 'USERNAME') ? 'user' : strtolower($key);
        $conn[$storageKey] = $env[$envKey] ?? '';
    }
    $optKey = "{$prefix}_OPTIONS";
    if (!empty($env[$optKey])) {
        $decoded = json_decode($env[$optKey], true);
        $conn['options'] = is_array($decoded) ? $decoded : [];
    }
    return $conn;
}

/**
 * Build env array from form data
 */
function buildEnvFromForm(array $data, string $driver): array
{
    global $DRIVER_ENV_KEYS, $DRIVER_ENV_PREFIX;
    $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
    $keys = $DRIVER_ENV_KEYS[$driver] ?? [];
    $env = [];

    $flatFilesDrivers = ['csv', 'ini', 'json', 'neon', 'xml', 'yaml'];

    foreach ($keys as $key) {
        $formKey = strtolower($key);
        $envKey = "{$prefix}_{$key}";
        $value = $data[$formKey] ?? $_ENV[$envKey] ?? '';

        if ($key === 'DATABASE' && in_array($driver, $flatFilesDrivers, true)) {
            $value = resolveFlatFilesDatabasePath($value, $driver);
        } elseif ($key === 'DATABASE' && $driver === 'sqlite') {
            $value = resolveSqliteDatabasePath($value);
        }

        $env[$envKey] = $value;
    }

    return $env;
}

/**
 * Resolve Flat Files database path: use default when empty, make relative paths absolute
 */
function resolveFlatFilesDatabasePath(string $path, string $driver): string
{
    $path = trim($path);
    $root = defined('PATH_ROOT') ? PATH_ROOT : getcwd();

    if ($path === '') {
        $path = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $driver;
    } elseif (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $stack = [];
        foreach ($parts as $p) {
            if ($p === '..') {
                if (!empty($stack)) {
                    array_pop($stack);
                }
            } elseif ($p !== '.' && $p !== '') {
                $stack[] = $p;
            }
        }
        $path = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $stack);
    }

    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }

    return $path;
}

/**
 * Resolve SQLite database path: use default when empty, make relative paths absolute
 */
function resolveSqliteDatabasePath(string $path): string
{
    $path = trim($path);
    $root = defined('PATH_ROOT') ? PATH_ROOT : getcwd();

    if ($path === '') {
        $dir = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'database';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'sqlite.db';
    }

    if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $stack = [];
        foreach ($parts as $p) {
            if ($p === '..') {
                if (!empty($stack)) {
                    array_pop($stack);
                }
            } elseif ($p !== '.' && $p !== '') {
                $stack[] = $p;
            }
        }
        $path = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $stack);
    }

    $dir = dirname($path);
    if ($dir !== '.' && !is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    // Normalize to forward slashes so Path::isAbsolute recognizes Windows paths (E:/ not E:\)
    return str_replace('\\', '/', $path);
}

/**
 * Get QueryBuilder class for engine
 */
function getQueryBuilderClass(string $engine, string $driver = ''): string
{
    $nativeMap = [
        'mysql' => 'MySQLiQueryBuilder',
        'pgsql' => 'PgSQLQueryBuilder',
        'sqlsrv' => 'SQLSrvQueryBuilder',
        'oci' => 'OCIQueryBuilder',
        'firebird' => 'FirebirdQueryBuilder',
        'sqlite' => 'SQLiteQueryBuilder',
    ];
    $flat_filesMap = [
        'csv' => 'CSVQueryBuilder',
        'ini' => 'INIQueryBuilder',
        'json' => 'JSONQueryBuilder',
        'neon' => 'NEONQueryBuilder',
        'xml' => 'XMLQueryBuilder',
        'yaml' => 'YAMLQueryBuilder',
    ];
    if ($engine === 'native') {
        return 'GenericDatabase\\Engine\\' . ($nativeMap[$driver] ?? 'MySQLiQueryBuilder');
    }
    if ($engine === 'flat_files') {
        return 'GenericDatabase\\Engine\\' . ($flat_filesMap[$driver] ?? 'JSONQueryBuilder');
    }
    $qb = in_array($engine, ['odbc', 'pdo_odbc']) ? 'ODBCQueryBuilder' : 'PDOQueryBuilder';
    return 'GenericDatabase\\Engine\\' . $qb;
}

function buildPhpConnectionContent(
    string $baseName,
    string $envFileName,
    string $module,
    string $engine,
    string $driver,
    bool $strategy,
    string $qbClass
): string {
    global $DRIVER_ENV_KEYS, $DRIVER_ENV_PREFIX;
    $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
    $keys = $DRIVER_ENV_KEYS[$driver] ?? [];
    $optKey = "{$prefix}_OPTIONS";

    // Connection class
    $connClassFqn = $strategy
        ? 'GenericDatabase\\Connection'
        : getConnectionClass($engine, $driver);
    $connClassShort = basename(str_replace('\\', '/', $connClassFqn));
    $qbShort = basename(str_replace('\\', '/', $qbClass));

    // Header + use statements
    $php = "<?php\n/**\n * Conexão: $baseName\n * Gerado pelo Assistente de Conexão - PHP Generic Database\n */\ndeclare(strict_types=1);\n\n";
    $php .= "use Dotenv\\Dotenv;\nuse $connClassFqn;\nuse $qbClass;\n\n";

    // PATH_ROOT, autoload, env loading
    $php .= "if (!defined('PATH_ROOT')) {\n    define('PATH_ROOT', dirname(__DIR__, 3));\n}\n\n";
    $php .= "require_once PATH_ROOT . '/vendor/autoload.php';\n\n";
    $php .= "if (file_exists(__DIR__ . '/$envFileName')) {\n    \Dotenv::createImmutable(__DIR__, '$envFileName')->load();\n} elseif (file_exists(PATH_ROOT . '/.env')) {\n    \Dotenv::createImmutable(PATH_ROOT)->load();\n}\n\n";

    // Options: resolve string constant keys/values to numeric
    // Build a class map so short names like "MySQL::X" resolve to FQN for defined()/constant()
    global $OPTIONS_CLASS_MAP;
    $php .= "\$classMap = [\n";
    foreach ($OPTIONS_CLASS_MAP as $short => $fqn) {
        $php .= "    '$short' => '$fqn',\n";
    }
    $php .= "];\n";
    $php .= "\$opts = json_decode(\$_ENV['$optKey'] ?? '[]', true);\n";
    $php .= "\$options = [];\n";
    $php .= "foreach (\$opts as \$k => \$v) {\n";
    $php .= "    \$rk = \$k;\n";
    $php .= "    if (preg_match('/^(\\\\w+)::(\\\\w+)\$/', \$k, \$m) && isset(\$classMap[\$m[1]])) {\n";
    $php .= "        \$rk = \$classMap[\$m[1]] . '::' . \$m[2];\n";
    $php .= "    }\n";
    $php .= "    \$rv = \$v;\n";
    $php .= "    if (is_string(\$v) && preg_match('/^(\\\\w+)::(\\\\w+)\$/', \$v, \$m2) && isset(\$classMap[\$m2[1]])) {\n";
    $php .= "        \$rv = \$classMap[\$m2[1]] . '::' . \$m2[2];\n";
    $php .= "    }\n";
    $php .= "    \$options[defined(\$rk) ? constant(\$rk) : \$k] = is_string(\$rv) && defined(\$rv) ? constant(\$rv) : \$v;\n";
    $php .= "}\n\n";

    // Map env keys to param/setter info
    $fieldMap = [
        'HOST'     => ['setter' => 'setHost',     'param' => 'host',     'cast' => ''],
        'PORT'     => ['setter' => 'setPort',     'param' => 'port',     'cast' => '(int) '],
        'DATABASE' => ['setter' => 'setDatabase', 'param' => 'database', 'cast' => ''],
        'USERNAME' => ['setter' => 'setUser',     'param' => 'user',     'cast' => ''],
        'PASSWORD' => ['setter' => 'setPassword', 'param' => 'password', 'cast' => ''],
        'CHARSET'  => ['setter' => 'setCharset',  'param' => 'charset',  'cast' => ''],
    ];

    $engineId = getStrategyEngineId($engine, $driver);

    if ($module === 'Chainable') {
        // (new Class())->setHost(...)->setPort(...)->...->setException(true);
        if ($strategy) {
            $php .= "\$context = (new $connClassShort())\n    ->setEngine('$engineId')";
        } else {
            $php .= "\$context = (new $connClassShort())";
        }
        if (needsSetDriver($engine)) {
            $php .= "\n    ->setDriver('$driver')";
        }
        foreach ($keys as $key) {
            $f = $fieldMap[$key] ?? null;
            if ($f) {
                $php .= "\n    ->{$f['setter']}({$f['cast']}\$_ENV['{$prefix}_{$key}'])";
            }
        }
        $php .= "\n    ->setOptions(\$options)";
        $php .= "\n    ->setException(true);\n";
    } elseif ($module === 'Fluent') {
        // Class::setHost(...)::setPort(...)::...::setException(true);
        if ($strategy) {
            $php .= "$connClassShort::setEngine('$engineId');\n";
        }
        $chain = [];
        if (needsSetDriver($engine)) {
            $chain[] = "setDriver('$driver')";
        }
        foreach ($keys as $key) {
            $f = $fieldMap[$key] ?? null;
            if ($f) {
                $chain[] = "{$f['setter']}({$f['cast']}\$_ENV['{$prefix}_{$key}'])";
            }
        }
        $chain[] = "setOptions(\$options)";
        $chain[] = "setException(true)";
        $first = array_shift($chain);
        $php .= "\$context = $connClassShort::$first";
        foreach ($chain as $call) {
            $php .= "\n    ::$call";
        }
        $php .= ";\n";
    } elseif ($module === 'StaticArgs') {
        // Class::new(engine: ..., host: ..., port: ..., ...);
        $php .= "\$context = $connClassShort::new(\n";
        $args = [];
        if ($strategy) {
            $args[] = "    engine: '$engineId'";
        }
        if (needsSetDriver($engine)) {
            $args[] = "    driver: '$driver'";
        }
        foreach ($keys as $key) {
            $f = $fieldMap[$key] ?? null;
            if ($f) {
                $args[] = "    {$f['param']}: {$f['cast']}\$_ENV['{$prefix}_{$key}']";
            }
        }
        $args[] = "    options: \$options";
        $args[] = "    exception: true";
        $php .= implode(",\n", $args) . "\n);\n";
    } elseif ($module === 'StaticArray') {
        // Class::new(['engine' => ..., 'host' => ..., ...]);
        $php .= "\$context = $connClassShort::new([\n";
        $args = [];
        if ($strategy) {
            $args[] = "    'engine' => '$engineId'";
        }
        if (needsSetDriver($engine)) {
            $args[] = "    'driver' => '$driver'";
        }
        foreach ($keys as $key) {
            $f = $fieldMap[$key] ?? null;
            if ($f) {
                $args[] = "    '{$f['param']}' => {$f['cast']}\$_ENV['{$prefix}_{$key}']";
            }
        }
        $args[] = "    'options' => \$options";
        $args[] = "    'exception' => true";
        $php .= implode(",\n", $args) . "\n]);\n";
    }

    $php .= "\n\$context = \$context->connect();\n\n";
    $php .= "// Exemplo: \$qb = $qbShort::with(\$context);\n";
    return $php;
}

/**
 * Build a module-based PHP connection file for preset connections.
 * Uses GenericDatabase\Modules\{\Module}::{method}(array $env, bool $persistent, bool $strategy).
 * Options are handled internally by the module - no classMap or options block needed.
 */
function buildPhpPresetContent(
    string $module,
    string $engine,
    string $driver,
    bool $strategy,
    string $qbClass
): string {
    global $DRIVER_ENV_KEYS, $DRIVER_ENV_PREFIX, $DRIVER_METHODS;

    $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
    $keys = $DRIVER_ENV_KEYS[$driver] ?? [];
    $methodName = $DRIVER_METHODS[$driver][$engine] ?? null;
    $qbShort = basename(str_replace('\\', '/', $qbClass));

    $moduleClassMap = [
        'Chainable'   => 'GenericDatabase\\Modules\\Chainable',
        'Fluent'      => 'GenericDatabase\\Modules\\Fluent',
        'StaticArgs'  => 'GenericDatabase\\Modules\\StaticArgs',
        'StaticArray' => 'GenericDatabase\\Modules\\StaticArray',
    ];
    $moduleClassFqn = $moduleClassMap[$module] ?? 'GenericDatabase\\Modules\\Chainable';

    // Header + use statements
    $php = "<?php\n/**\n * Conexão Preset: $module / " . ucfirst($engine) . " / " . ucfirst($driver) . "\n";
    $php .= " * Gerado pelo Assistente de Conexão - PHP Generic Database\n */\ndeclare(strict_types=1);\n\n";
    $php .= "use Dotenv\\Dotenv;\nuse $moduleClassFqn;\n\n";

    // PATH_ROOT, autoload, env loading (system .env only - no per-connection env file)
    $php .= "if (!defined('PATH_ROOT')) {\n    define('PATH_ROOT', dirname(__DIR__, 3));\n}\n\n";
    $php .= "require_once PATH_ROOT . '/vendor/autoload.php';\n\n";
    $php .= "if (file_exists(PATH_ROOT . '/.env')) {\n    \Dotenv::createImmutable(PATH_ROOT)->load();\n}\n\n";

    // Build $env array from system .env variables
    $fieldMap = [
        'HOST'     => ['param' => 'host',     'cast' => ''],
        'PORT'     => ['param' => 'port',     'cast' => '(int) '],
        'DATABASE' => ['param' => 'database', 'cast' => ''],
        'USERNAME' => ['param' => 'user',     'cast' => ''],
        'PASSWORD' => ['param' => 'password', 'cast' => ''],
        'CHARSET'  => ['param' => 'charset',  'cast' => ''],
    ];
    $php .= "\$env = [\n";
    foreach ($keys as $key) {
        $f = $fieldMap[$key] ?? null;
        if ($f) {
            $cast = $f['cast'];
            $php .= "    '{$f['param']}' => {$cast}(\$_ENV['{$prefix}_{$key}'] ?? ''),\n";
        }
    }
    $php .= "];\n\n";

    // Module call: Module::method($env, $persistent = false, $strategy)
    $strategyBool = $strategy ? 'true' : 'false';
    if ($methodName) {
        $php .= "\$context = $module::$methodName(\$env, false, $strategyBool);\n";
    }

    $php .= "\n\$context = \$context->connect();\n\n";
    $php .= "// Exemplo: \$qb = $qbShort::with(\$context);\n";
    return $php;
}

function buildConfig(): array
{
    // Return raw TOML values so the Settings modal shows exactly what is on disk.
    $settings = read_toml_file(WIZARD_SETTINGS_FILE);

    $config = ['active_connection' => null, 'connection_data' => null, 'settings' => $settings];
    if (file_exists(WIZARD_ACTIVE_PROFILE_FILE)) {
        $loaded = json_decode(file_get_contents(WIZARD_ACTIVE_PROFILE_FILE), true) ?: [];
        // Only carry active_connection from the JSON; settings come from TOML constants.
        $config['active_connection'] = $loaded['active_connection'] ?? null;
    }
    $source = strtolower($config['active_connection']['source'] ?? '');
    $needConnectionData = ($source === 'custom');
    if ($needConnectionData) {
        $name = $config['active_connection']['name'] ?? '';
        $profilesPath = WIZARD_PROFILES_FILE;
        $connMeta = null;
        if ($name && file_exists($profilesPath)) {
            $data = json_decode(file_get_contents($profilesPath), true);
            if (is_array($data)) {
                foreach ($data as $c) {
                    // Support both old key (connection_name) and new key (name)
                    if (($c['name'] ?? $c['connection_name'] ?? '') === $name) {
                        $connMeta = $c;
                        break;
                    }
                }
            }
        }
        $config['connection_data'] = $connMeta;
        if ($connMeta && $name) {
            $env = loadEnvFromConnection($name);
            $driver = fromDriverLabel($config['active_connection']['driver'] ?? '', fromEngineLabel($config['active_connection']['engine'] ?? ''));
            $connData = buildConnectionDataFromEnv($env, $driver);
            if (!empty($connData['options'])) {
                $engine = fromEngineLabel($config['active_connection']['engine'] ?? '');
                $connData['options'] = normalizeOptionsForDisplay($connData['options'], $engine);
            }
            $config['connection_data']['connection_data'] = $connData;
        }
    }
    return $config;
}

/**
 * Build a native \PDO connection from the active connection's env for query execution.
 * Supports all SQL drivers; throws for flat files (no SQL).
 */
/**
 * Build and connect the active connection using its native engine class (MySQLiConnection, PDOConnection, etc.).
 * Respects the engine/driver configured in active_profile.json instead of always using raw PDO.
 */
function buildConnectionForQuery(array $config): \GenericDatabase\Interfaces\IConnection
{
    $engineLabel = $config['active_connection']['engine'] ?? '';
    $driverLabel = $config['active_connection']['driver'] ?? '';
    $source      = $config['active_connection']['source'] ?? 'Custom';
    $name        = $config['active_connection']['name'] ?? '';

    $engine = fromEngineLabel($engineLabel);
    $driver = fromDriverLabel($driverLabel, $engine);

    if ($engine === 'flat_files') {
        throw new \Exception('Flat file connections do not support SQL queries.');
    }

    if ($source === 'Custom') {
        $env = loadEnvFromConnection($name);
    } else {
        $root = defined('PATH_ROOT') ? PATH_ROOT : dirname(__DIR__);
        $env = parseEnvFile($root . '/.env');
    }

    $instance = buildConnectionInstance($engine, $driver, $env, false);
    $instance->connect();
    return $instance;
}

function buildPdoForQuery(array $config): \PDO
{
    global $DRIVER_ENV_PREFIX;

    $name = $config['active_connection']['name'] ?? '';
    $source = $config['active_connection']['source'] ?? 'Custom';
    $engineLabel = $config['active_connection']['engine'] ?? '';
    $driverLabel = $config['active_connection']['driver'] ?? '';

    $engine = fromEngineLabel($engineLabel);
    $driver = fromDriverLabel($driverLabel, $engine);

    if ($engine === 'flat_files') {
        throw new \Exception('Flat file connections do not support SQL queries.');
    }

    // Load env vars
    if ($source === 'Custom') {
        $env = loadEnvFromConnection($name);
    } else {
        $root = defined('PATH_ROOT') ? PATH_ROOT : dirname(__DIR__);
        $env = parseEnvFile($root . '/.env');
    }

    $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
    $host     = $env["{$prefix}_HOST"]     ?? '';
    $port     = (int) ($env["{$prefix}_PORT"] ?? 0);
    $database = $env["{$prefix}_DATABASE"] ?? '';
    $user     = $env["{$prefix}_USERNAME"] ?? '';
    $password = $env["{$prefix}_PASSWORD"] ?? '';
    $charset  = $env["{$prefix}_CHARSET"]  ?? 'utf8';

    $pdoOptions = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ];

    switch ($driver) {
        case 'mysql':
            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
            // Required for named params in LIMIT/OFFSET clauses (MySQL native prepares reject string-typed params there)
            $pdoOptions[\PDO::ATTR_EMULATE_PREPARES] = true;
            break;
        case 'pgsql':
            $dsn = "pgsql:host=$host;port=$port;dbname=$database";
            break;
        case 'sqlsrv':
            $dsn = "sqlsrv:Server=$host,$port;Database=$database";
            $user = $user ?: null;
            $password = $password ?: null;
            break;
        case 'oci':
            $dsn = "oci:dbname=//$host:$port/$database";
            if ($charset) {
                $dsn .= ";charset=$charset";
            }
            break;
        case 'firebird':
            $dsn = "firebird:host=$host;dbname=$database;charset=$charset";
            break;
        case 'sqlite':
            $dsn = "sqlite:$database";
            $user = null;
            $password = null;
            break;
        default:
            throw new \Exception("Unsupported driver for direct query: $driver");
    }

    return new \PDO($dsn, $user ?: null, $password ?: null, $pdoOptions);
}

/**
 * Load the active connection name from active_profile.json, or '' if none.
 */
function getActiveConnectionName(): string
{
    $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
    if (!file_exists($activeProfilePath)) {
        return '';
    }
    $config = json_decode(file_get_contents($activeProfilePath), true) ?: [];
    return $config['active_connection']['name'] ?? '';
}

// ── SQL → QB Converter ───────────────────────────────────────────────────────

/**
 * Extract the content inside balanced parentheses starting at $start (must be '(').
 * Returns the inner string or null if unbalanced.
 */
function extractBalancedContent(string $s, int $start): ?string
{
    if ($start >= strlen($s) || $s[$start] !== '(') {
        return null;
    }
    $depth = 0;
    $inner = '';
    for ($i = $start, $len = strlen($s); $i < $len; $i++) {
        $c = $s[$i];
        if ($c === '(') {
            if ($depth > 0) {
                $inner .= $c;
            }
            $depth++;
        } elseif ($c === ')') {
            $depth--;
            if ($depth === 0) {
                return $inner;
            }
            $inner .= $c;
        } else {
            if ($depth > 0) {
                $inner .= $c;
            }
        }
    }
    return null;
}

/**
 * Detect if $whereContent is purely EXISTS (...) or NOT EXISTS (...).
 * Returns ['negate' => bool, 'inner' => string] or null.
 */
function detectExistsPattern(string $whereContent): ?array
{
    $trimmed = trim($whereContent);
    $negate = false;
    $offset = 0;
    if (preg_match('/^NOT\s+EXISTS\s*/i', $trimmed, $m)) {
        $negate = true;
        $offset = strlen($m[0]);
    } elseif (preg_match('/^EXISTS\s*/i', $trimmed, $m)) {
        $offset = strlen($m[0]);
    } else {
        return null;
    }
    if ($offset >= strlen($trimmed) || $trimmed[$offset] !== '(') {
        return null;
    }
    $inner = extractBalancedContent($trimmed, $offset);
    if ($inner === null) {
        return null;
    }
    $closingPos = $offset + strlen($inner) + 2; // +2 for the ( and )
    $rest = trim(substr($trimmed, $closingPos));
    if ($rest !== '') {
        return null; // trailing content → not a pure EXISTS clause
    }
    return ['negate' => $negate, 'inner' => trim($inner)];
}

/**
 * Try to parse a single WHERE condition as "col op val".
 * Returns ['col', 'op', 'val'] or null.
 */
function parseSimpleWhereCondition(string $condition): ?array
{
    $condition = trim($condition);
    // Check for top-level AND/OR — if present, it's not a single simple condition
    $depth = 0;
    for ($i = 0, $len = strlen($condition); $i < $len; $i++) {
        $c = $condition[$i];
        if ($c === '(') {
            $depth++;
            continue;
        }
        if ($c === ')') {
            $depth--;
            continue;
        }
        if ($depth === 0 && preg_match('/^(?:AND|OR)\s/i', substr($condition, $i))) {
            return null;
        }
    }
    // Multi-char operators first to avoid false matches
    $ops = [
        'NOT LIKE',
        'NOT IN',
        'NOT BETWEEN',
        'IS NOT NULL',
        'IS NULL',
        'LIKE',
        'IN',
        'BETWEEN',
        '<=',
        '>=',
        '<>',
        '!=',
        '<',
        '>',
        '='
    ];
    foreach ($ops as $op) {
        $needle = ' ' . $op . ' ';
        $pos = stripos($condition, $needle);
        if ($pos !== false) {
            $col = trim(substr($condition, 0, $pos));
            $val = trim(substr($condition, $pos + strlen($needle)));
            if ($col !== '' && $val !== '') {
                return ['col' => $col, 'op' => strtoupper($op), 'val' => $val];
            }
        }
    }
    return null;
}

/**
 * Indent nested QB code for embedding inside a method call (8-space prefix).
 */
function formatNestedQb(string $code): string
{
    $code = rtrim($code, ';');
    $lines = explode("\n", $code);
    return implode("\n", array_map(fn(string $l) => '        ' . $l, $lines));
}

/**
 * Generate the ->where / ->whereExists / ->whereNotExists call for a WHERE clause.
 */
function generateWhereCode(string $whereContent, string $qbClass): string
{
    $q = fn(string $s): string => "'" . str_replace("'", "\\'", $s) . "'";

    $exists = detectExistsPattern($whereContent);
    if ($exists !== null) {
        $method = $exists['negate'] ? 'whereNotExists' : 'whereExists';
        $innerCode = parseSingleSelect($exists['inner'], $qbClass, true);
        $formatted = formatNestedQb($innerCode);
        return "\n    ->{$method}(\n" . $formatted . "\n    )";
    }

    $simple = parseSimpleWhereCondition($whereContent);
    if ($simple !== null) {
        return "\n    ->where(" . $q($simple['col']) . ", " . $q($simple['op']) . ", " . $q($simple['val']) . ")";
    }

    return "\n    ->where(" . $q($whereContent) . ")";
}

/**
 * Convert a SQL string to QueryBuilder PHP code.
 */
function sqlToQbCode(string $sql, string $qbClass = ''): string
{
    $sql = preg_replace('/\s+/', ' ', trim($sql));
    if ($sql === '') {
        return '';
    }
    $parts = splitTopLevel($sql, ['UNION ALL', 'UNION']);
    if (count($parts) > 1) {
        $code = rtrim(parseSingleSelect($parts[0]['text'], $qbClass), ';');
        for ($i = 1; $i < count($parts); $i++) {
            $m = (strtoupper(trim($parts[$i]['sep'] ?? '')) === 'UNION ALL') ? 'unionAll' : 'union';
            $innerCode = parseSingleSelect($parts[$i]['text'], $qbClass, true);
            $sub = formatNestedQb($innerCode);
            $code .= "\n    ->{$m}(\n" . $sub . "\n    )";
        }
        return $code . ';';
    }
    return parseSingleSelect($sql, $qbClass);
}

function parseSingleSelect(string $sql, string $qbClass = '', bool $isNested = false): string
{
    $sql = preg_replace('/\s+/', ' ', trim($sql));
    $distinct = (bool)preg_match('/^SELECT\s+DISTINCT\b/i', $sql);
    $sql = preg_replace('/^SELECT\s+(?:DISTINCT\s+)?/i', '', $sql, 1);

    $boundaries = findClauseBoundaries($sql);
    $clauses = ['__SELECT__' => '', 'FROM' => '', 'WHERE' => '', 'GROUP BY' => '', 'HAVING' => '', 'ORDER BY' => '', 'LIMIT' => '', 'OFFSET' => ''];
    $joins = [];
    $joinTypes = ['LEFT OUTER JOIN', 'RIGHT OUTER JOIN', 'FULL OUTER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL JOIN', 'INNER JOIN', 'CROSS JOIN', 'JOIN'];

    foreach ($boundaries as $idx => $b) {
        $nextPos = isset($boundaries[$idx + 1]) ? $boundaries[$idx + 1]['pos'] : strlen($sql);
        $content = trim(substr($sql, $b['pos'] + $b['kwLen'], $nextPos - $b['pos'] - $b['kwLen']));
        $kw = $b['keyword'];
        if (in_array($kw, $joinTypes, true)) {
            $joins[] = ['type' => $kw, 'clause' => $content];
        } elseif (array_key_exists($kw, $clauses)) {
            $clauses[$kw] = $content;
        }
    }

    $q = fn(string $s): string => "'" . str_replace("'", "\\'", $s) . "'";

    // Build the opening: outer query gets assignment, nested queries get just the constructor
    if ($isNested) {
        $code = $qbClass !== '' ? "(new {$qbClass}(\$context))" : '(new QueryBuilder($context))';
    } else {
        $code = $qbClass !== '' ? "\$qb = (new {$qbClass}(\$context))" : '$qb';
    }

    if ($distinct) {
        $code .= "\n    ->distinct()";
    }
    if ($clauses['__SELECT__'] !== '') {
        $cols = splitTopLevelComma($clauses['__SELECT__']);
        $code .= "\n    ->select(" . implode(', ', array_map(fn($c) => $q(trim($c)), $cols)) . ")";
    }
    if ($clauses['FROM'] !== '') {
        // Keep table + alias as a single string: ->from('estado e')
        $code .= "\n    ->from(" . $q(trim($clauses['FROM'])) . ")";
    }
    foreach ($joins as $join) {
        $t = strtoupper($join['type']);
        $m = str_contains($t, 'LEFT') ? 'leftJoin'
            : (str_contains($t, 'RIGHT') ? 'rightJoin'
                : (str_contains($t, 'CROSS') ? 'crossJoin'
                    : (str_contains($t, 'FULL') ? 'fullJoin' : 'innerJoin')));
        $code .= "\n    ->{$m}(" . $q(trim($join['clause'])) . ")";
    }
    if ($clauses['WHERE'] !== '') {
        $code .= generateWhereCode($clauses['WHERE'], $qbClass);
    }
    if ($clauses['GROUP BY'] !== '') {
        $cols = splitTopLevelComma($clauses['GROUP BY']);
        $code .= "\n    ->group(" . implode(', ', array_map(fn($c) => $q(trim($c)), $cols)) . ")";
    }
    if ($clauses['HAVING'] !== '') {
        $code .= "\n    ->having(" . $q($clauses['HAVING']) . ")";
    }
    if ($clauses['ORDER BY'] !== '') {
        foreach (splitTopLevelComma($clauses['ORDER BY']) as $colExpr) {
            $colExpr = trim($colExpr);
            // Extract direction if present, default to ASC
            if (preg_match('/^(.*?)\s+(ASC|DESC)\s*$/i', $colExpr, $dm)) {
                $dir = strtoupper($dm[2]);
                $colName = trim($dm[1]);
            } else {
                $dir = 'ASC';
                $colName = $colExpr;
            }
            // Strip table alias prefix (e.g. e.nome → nome)
            if (preg_match('/^\w+\.(\w+)$/', $colName, $cm)) {
                $colName = $cm[1];
            }
            $code .= "\n    ->order(" . $q($colName . ' ' . $dir) . ")";
        }
    }
    if ($clauses['LIMIT'] !== '') {
        $lv = trim($clauses['LIMIT']);
        $code .= "\n    ->limit(" . (is_numeric($lv) ? $lv : $q($lv)) . ")";
    }
    if ($clauses['OFFSET'] !== '') {
        $ov = trim($clauses['OFFSET']);
        $code .= "\n    ->offset(" . (is_numeric($ov) ? $ov : $q($ov)) . ")";
    }
    return $code . ';';
}

/**
 * Find positions of top-level SQL clause keywords (not inside parens/strings).
 */
function findClauseBoundaries(string $sql): array
{
    $keywords = [
        'LEFT OUTER JOIN',
        'RIGHT OUTER JOIN',
        'FULL OUTER JOIN',
        'LEFT JOIN',
        'RIGHT JOIN',
        'FULL JOIN',
        'INNER JOIN',
        'CROSS JOIN',
        'JOIN',
        'GROUP BY',
        'ORDER BY',
        'FROM',
        'WHERE',
        'HAVING',
        'LIMIT',
        'OFFSET',
    ];
    usort($keywords, fn($a, $b) => strlen($b) - strlen($a));

    $len = strlen($sql);
    $depth = 0;
    $inStr = false;
    $strChar = '';
    $found = [['keyword' => '__SELECT__', 'pos' => 0, 'kwLen' => 0]];
    $i = 0;

    while ($i < $len) {
        $c = $sql[$i];
        if (!$inStr && ($c === "'" || $c === '"' || $c === '`')) {
            $inStr = true;
            $strChar = $c;
            $i++;
            continue;
        }
        if ($inStr) {
            if ($c === $strChar) {
                $inStr = false;
            }
            $i++;
            continue;
        }
        if ($c === '(') {
            $depth++;
            $i++;
            continue;
        }
        if ($c === ')') {
            if ($depth > 0) {
                $depth--;
            }
            $i++;
            continue;
        }
        if ($depth === 0) {
            $prev = $i > 0 ? $sql[$i - 1] : ' ';
            if (!ctype_alnum($prev) && $prev !== '_') {
                foreach ($keywords as $kw) {
                    $kLen = strlen($kw);
                    if ($i + $kLen > $len) {
                        continue;
                    }
                    if (strncasecmp(substr($sql, $i), $kw, $kLen) !== 0) {
                        continue;
                    }
                    $nextChar = ($i + $kLen < $len) ? $sql[$i + $kLen] : ' ';
                    if (!ctype_alnum($nextChar) && $nextChar !== '_') {
                        $found[] = ['keyword' => strtoupper($kw), 'pos' => $i, 'kwLen' => $kLen];
                        $i += $kLen;
                        continue 2;
                    }
                }
            }
        }
        $i++;
    }
    return $found;
}

/**
 * Split SQL by top-level separator keywords (UNION, UNION ALL).
 */
function splitTopLevel(string $sql, array $keywords): array
{
    usort($keywords, fn($a, $b) => strlen($b) - strlen($a));
    $len = strlen($sql);
    $depth = 0;
    $inStr = false;
    $strChar = '';
    $result = [];
    $current = '';
    $sep = '';
    $i = 0;

    while ($i < $len) {
        $c = $sql[$i];
        if (!$inStr && ($c === "'" || $c === '"')) {
            $inStr = true;
            $strChar = $c;
            $current .= $c;
            $i++;
            continue;
        }
        if ($inStr) {
            if ($c === $strChar) {
                $inStr = false;
            }
            $current .= $c;
            $i++;
            continue;
        }
        if ($c === '(') {
            $depth++;
            $current .= $c;
            $i++;
            continue;
        }
        if ($c === ')') {
            $depth--;
            $current .= $c;
            $i++;
            continue;
        }
        if ($depth === 0) {
            $prev = $i > 0 ? $sql[$i - 1] : ' ';
            if (!ctype_alnum($prev) && $prev !== '_') {
                foreach ($keywords as $kw) {
                    $kLen = strlen($kw);
                    if ($i + $kLen > $len) {
                        continue;
                    }
                    if (strncasecmp(substr($sql, $i), $kw, $kLen) !== 0) {
                        continue;
                    }
                    $nextChar = ($i + $kLen < $len) ? $sql[$i + $kLen] : ' ';
                    if (!ctype_alnum($nextChar) && $nextChar !== '_') {
                        $result[] = ['text' => trim($current), 'sep' => $sep];
                        $sep = $kw;
                        $current = '';
                        $i += $kLen;
                        if ($i < $len && $sql[$i] === ' ') {
                            $i++;
                        }
                        continue 2;
                    }
                }
            }
        }
        $current .= $c;
        $i++;
    }
    $result[] = ['text' => trim($current), 'sep' => $sep];
    return $result;
}

/**
 * Split a string by commas at depth 0 (respects parentheses and strings).
 */
function splitTopLevelComma(string $s): array
{
    $result = [];
    $current = '';
    $depth = 0;
    $inStr = false;
    $strChar = '';
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = $s[$i];
        if (!$inStr && ($c === "'" || $c === '"')) {
            $inStr = true;
            $strChar = $c;
            $current .= $c;
            continue;
        }
        if ($inStr) {
            if ($c === $strChar) {
                $inStr = false;
            }
            $current .= $c;
            continue;
        }
        if ($c === '(') {
            $depth++;
            $current .= $c;
            continue;
        }
        if ($c === ')') {
            $depth--;
            $current .= $c;
            continue;
        }
        if ($c === ',' && $depth === 0) {
            $result[] = trim($current);
            $current = '';
            continue;
        }
        $current .= $c;
    }
    if (trim($current) !== '') {
        $result[] = trim($current);
    }
    return $result;
}

/**
 * Parse a table reference: "table", "table alias", "table AS alias".
 */
function parseTableRef(string $ref): array
{
    $ref = preg_replace('/\s+/', ' ', trim($ref));
    if (preg_match('/^(\S+)\s+AS\s+(\S+)/i', $ref, $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    if (preg_match('/^(\S+)\s+(\S+)$/', $ref, $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    return [trim(preg_replace('/\s.*/', '', $ref)), ''];
}

// ── End SQL→QB Converter ────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'test_connection' || $action === 'complete_connection') {
        $module = $_POST['module'] ?? 'Chainable';
        $engine = $_POST['engine'] ?? '';
        $driver = $_POST['driver'] ?? '';
        $strategy = ($_POST['instance_type'] ?? 'specific') === 'strategy';
        $optionsText = $_POST['options'] ?? '';

        if (empty($engine) || empty($driver)) {
            throw new \Exception('Engine e Driver são obrigatórios.');
        }

        $env = buildEnvFromForm($_POST, $driver);
        $options = parseOptions($optionsText, $engine);

        $instance = buildConnectionInstance($engine, $driver, $env, $strategy);

        if (!empty($options)) {
            $optsForConn = optionsForConnection($options, $engine);
            $instance->setOptions($optsForConn);
        }

        $instance->connect();

        if ($action === 'test_connection') {
            $debug = isset($_POST['debug']) && $_POST['debug'] === '1';
            $debugOutput = '';
            if ($debug) {
                $debugOutput = print_r($instance, true);
            }
            $instance->disconnect();
            $response = ['success' => true, 'message' => 'OK'];
            if ($debug) {
                $response['debug'] = $debugOutput;
            }
            if (ob_get_level()) {
                ob_end_clean();
            }
            echo json_encode($response);
            exit;
        }

        // complete_connection - create .custom_{name} and custom_{name}.php in data/profiles
        $rawName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['connection_name'] ?? 'connection');
        // Strip existing custom_ prefix to avoid doubling
        $rawName = preg_replace('/^custom_/', '', $rawName);
        $connectionName = 'custom_' . $rawName;
        $dataDir = WIZARD_DATA_DIR;
        $connectionsDir = WIZARD_PROFILES_DIR;
        if (!is_dir($connectionsDir)) {
            mkdir($connectionsDir, 0755, true);
        }

        $baseName = $connectionName;
        $envFileName = '.' . $baseName;
        $envPath = $connectionsDir . '/' . $envFileName;
        $phpPath = $connectionsDir . '/' . $baseName . '.php';
        $counter = 1;
        while (file_exists($phpPath)) {
            $baseName = $connectionName . '_' . $counter;
            $envFileName = '.' . $baseName;
            $envPath = $connectionsDir . '/' . $envFileName;
            $phpPath = $connectionsDir . '/' . $baseName . '.php';
            $counter++;
        }

        // Build .{name} env file (dados de conexão + options em JSON)
        // Store options with string keys (e.g. "MySQL::ATTR_PERSISTENT") matching DSN JSON format
        $varLines = [];
        foreach ($env as $k => $v) {
            $varLines[] = "$k=\"$v\"";
        }
        $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
        if (!empty($options)) {
            $jsonStr = json_encode($options, JSON_UNESCAPED_UNICODE);
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $jsonStr);
            $varLines[] = "{$prefix}_OPTIONS=\"$escaped\"";
        }
        $varContent = implode("\n", $varLines);
        if (file_put_contents($envPath, $varContent) === false) {
            throw new \Exception('Não foi possível criar o arquivo de variáveis');
        }

        $qbClass = getQueryBuilderClass($engine, $driver);
        $phpContent = buildPhpConnectionContent($baseName, $envFileName, $module, $engine, $driver, $strategy, $qbClass);

        if (file_put_contents($phpPath, $phpContent) === false) {
            throw new \Exception('Não foi possível criar o arquivo PHP da conexão');
        }

        $engineLabel = toEngineLabel($engine);
        $driverLabel = toDriverLabel($driver, $engine);
        $typeLabel = toInstanceTypeLabel($strategy ? 'strategy' : 'specific');

        // Update active_profile.json - only active_connection, no presets key
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $existingConfig = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        unset($existingConfig['presets']);
        $existingConfig['active_connection'] = [
            'source' => 'Custom',
            'name' => $baseName,
            'module' => $module,
            'engine' => $engineLabel,
            'driver' => $driverLabel,
            'type' => $typeLabel,
            'env_file' => 'profiles/' . $envFileName,
            'php_file' => 'profiles/' . $baseName . '.php',
        ];
        file_put_contents($activeProfilePath, json_encode($existingConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Update profiles.json
        $profilesPath = WIZARD_PROFILES_FILE;
        $data = file_exists($profilesPath) ? json_decode(file_get_contents($profilesPath), true) : [];
        if (!is_array($data)) {
            $data = [];
        }
        $connectionData = [
            'name' => $baseName,
            'env_file' => './profiles/' . $envFileName,
            'php_file' => './profiles/' . $baseName . '.php',
            'source' => 'Custom',
            'module' => $module,
            'engine' => $engineLabel,
            'driver' => $driverLabel,
            'type' => $typeLabel,
        ];
        $data = array_values(array_filter($data, fn($c) => ($c['name'] ?? $c['connection_name'] ?? '') !== $baseName));
        $data[] = $connectionData;
        file_put_contents($profilesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $instance->disconnect();

        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode([
            'success' => true,
            'message' => 'Conexão criada com sucesso!',
            'files' => [
                'env' => $envPath,
                'php' => $phpPath,
            ],
            'config' => buildConfig(),
        ]);
        exit;
    }

    if ($action === 'update_connection') {
        $editName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['edit_connection_name'] ?? '');
        $rawName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['connection_name'] ?? '');
        $rawName = preg_replace('/^custom_/', '', $rawName);
        $connectionName = 'custom_' . $rawName;
        if (empty($editName) || empty($connectionName)) {
            throw new \Exception('Nome da conexão é obrigatório.');
        }
        $module = $_POST['module'] ?? 'Chainable';
        $engine = $_POST['engine'] ?? '';
        $driver = $_POST['driver'] ?? '';
        $strategy = ($_POST['instance_type'] ?? 'specific') === 'strategy';
        if (empty($engine) || empty($driver)) {
            throw new \Exception('Engine e Driver são obrigatórios.');
        }
        $env = buildEnvFromForm($_POST, $driver);
        $optionsText = $_POST['options'] ?? '';
        $options = parseOptions($optionsText, $engine);

        $instance = buildConnectionInstance($engine, $driver, $env, $strategy);
        if (!empty($options)) {
            $optsForConn = optionsForConnection($options, $engine);
            $instance->setOptions($optsForConn);
        }
        $instance->connect();
        $instance->disconnect();

        $dataDir = WIZARD_DATA_DIR;
        $connectionsDir = WIZARD_PROFILES_DIR;
        if (!is_dir($connectionsDir)) {
            mkdir($connectionsDir, 0755, true);
        }

        $baseName = $connectionName;
        $envFileName = '.' . $baseName;
        $envPath = $connectionsDir . '/' . $envFileName;
        $phpPath = $connectionsDir . '/' . $baseName . '.php';

        if ($editName !== $baseName) {
            $oldEnvPath = $connectionsDir . '/.' . $editName;
            $oldPhpPath = $connectionsDir . '/' . $editName . '.php';
            if (file_exists($oldEnvPath)) unlink($oldEnvPath);
            if (file_exists($oldPhpPath)) unlink($oldPhpPath);
        }

        $varLines = [];
        foreach ($env as $k => $v) {
            $varLines[] = "$k=\"$v\"";
        }
        $prefix = $DRIVER_ENV_PREFIX[$driver] ?? strtoupper($driver);
        if (!empty($options)) {
            $jsonStr = json_encode($options, JSON_UNESCAPED_UNICODE);
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $jsonStr);
            $varLines[] = "{$prefix}_OPTIONS=\"$escaped\"";
        }
        file_put_contents($envPath, implode("\n", $varLines));

        $qbClass = getQueryBuilderClass($engine, $driver);
        $phpContent = buildPhpConnectionContent($baseName, $envFileName, $module, $engine, $driver, $strategy, $qbClass);
        file_put_contents($phpPath, $phpContent);

        $profilesPath = WIZARD_PROFILES_FILE;
        $data = file_exists($profilesPath) ? json_decode(file_get_contents($profilesPath), true) : [];
        if (!is_array($data)) $data = [];
        $engineLabel = toEngineLabel($engine);
        $driverLabel = toDriverLabel($driver, $engine);
        $typeLabel = toInstanceTypeLabel($strategy ? 'strategy' : 'specific');

        $data = array_values(array_filter($data, fn($c) => ($c['name'] ?? $c['connection_name'] ?? '') !== $editName));
        $connectionData = [
            'name' => $baseName,
            'env_file' => './profiles/' . $envFileName,
            'php_file' => './profiles/' . $baseName . '.php',
            'source' => 'Custom',
            'module' => $module,
            'engine' => $engineLabel,
            'driver' => $driverLabel,
            'type' => $typeLabel,
        ];
        $data[] = $connectionData;
        file_put_contents($profilesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        unset($config['presets']);
        if (($config['active_connection']['name'] ?? '') === $editName) {
            $config['active_connection'] = [
                'source' => 'Custom',
                'name' => $baseName,
                'module' => $module,
                'engine' => $engineLabel,
                'driver' => $driverLabel,
                'type' => $typeLabel,
                'env_file' => 'profiles/' . $envFileName,
                'php_file' => 'profiles/' . $baseName . '.php',
            ];
        }
        file_put_contents($activeProfilePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Conexão atualizada com sucesso!', 'config' => buildConfig()]);
        exit;
    }

    if ($action === 'confirm_preset') {
        $module = $_POST['module'] ?? 'Chainable';
        $engine = $_POST['engine'] ?? '';
        $driver = $_POST['driver'] ?? '';
        $strategy = (strtolower($_POST['instance_type'] ?? 'specific') === 'strategy');
        if (empty($engine) || empty($driver)) {
            throw new \Exception('Engine e Driver são obrigatórios.');
        }
        $engineLabel = toEngineLabel($engine);
        $driverLabel = toDriverLabel($driver, $engine);
        $typeLabel = toInstanceTypeLabel($strategy ? 'strategy' : 'specific');
        $presetName = 'preset_' . strtolower($module) . '_' . $engine . '_' . $driver;

        $dataDir = WIZARD_DATA_DIR;
        $connectionsDir = WIZARD_PROFILES_DIR;
        if (!is_dir($connectionsDir)) {
            mkdir($connectionsDir, 0755, true);
        }

        // Generate module-based PHP file for preset
        $qbClass = getQueryBuilderClass($engine, $driver);
        $phpContent = buildPhpPresetContent($module, $engine, $driver, $strategy, $qbClass);
        $phpPath = $connectionsDir . '/' . $presetName . '.php';
        file_put_contents($phpPath, $phpContent);

        // Add/update preset entry in profiles.json
        $profilesPath = WIZARD_PROFILES_FILE;
        $data = file_exists($profilesPath) ? (json_decode(file_get_contents($profilesPath), true) ?: []) : [];
        if (!is_array($data)) {
            $data = [];
        }
        $presetEntry = [
            'name' => $presetName,
            'env_file' => null,
            'php_file' => 'profiles/' . $presetName . '.php',
            'source' => 'Preset',
            'module' => $module,
            'engine' => $engineLabel,
            'driver' => $driverLabel,
            'type' => $typeLabel,
        ];
        $data = array_values(array_filter($data, fn($p) => ($p['name'] ?? '') !== $presetName));
        $data[] = $presetEntry;
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        file_put_contents($profilesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Update active_profile.json - only active_connection, no presets key
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        unset($config['presets']);
        $config['active_connection'] = [
            'source' => 'Preset',
            'name' => $presetName,
            'module' => $module,
            'engine' => $engineLabel,
            'driver' => $driverLabel,
            'type' => $typeLabel,
            'php_file' => 'profiles/' . $presetName . '.php',
        ];
        file_put_contents($activeProfilePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Conexão pré-existente definida como ativa.', 'config' => buildConfig()]);
        exit;
    }

    if ($action === 'confirm_custom') {
        $connectionName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['connection_name'] ?? '');
        if (empty($connectionName)) {
            throw new \Exception('Nome da conexão é obrigatório.');
        }
        $dataDir = WIZARD_DATA_DIR;
        $profilesPath = WIZARD_PROFILES_FILE;
        if (!file_exists($profilesPath)) {
            throw new \Exception('Nenhuma conexão customizada encontrada.');
        }
        $data = json_decode(file_get_contents($profilesPath), true);
        if (!is_array($data)) {
            throw new \Exception('Dados inválidos.');
        }
        $found = null;
        foreach ($data as $c) {
            if (($c['name'] ?? $c['connection_name'] ?? '') === $connectionName) {
                $found = $c;
                break;
            }
        }
        if (!$found) {
            throw new \Exception('Conexão não encontrada.');
        }
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        $config['active_connection'] = [
            'source' => 'Custom',
            'name' => $connectionName,
            'module' => $found['module'] ?? 'Chainable',
            'engine' => $found['engine'] ?? '',
            'driver' => $found['driver'] ?? '',
            'type' => $found['type'] ?? $found['instance_type'] ?? 'Specific',
            'env_file' => ltrim($found['env_file'] ?? $found['env'] ?? 'profiles/.' . $connectionName, './'),
            'php_file' => ltrim($found['php_file'] ?? $found['test_file'] ?? 'profiles/' . $connectionName . '.php', './'),
        ];
        file_put_contents($activeProfilePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Conexão customizada definida como ativa.', 'config' => buildConfig()]);
        exit;
    }

    if ($action === 'get_config') {
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(buildConfig());
        exit;
    }

    if ($action === 'get_connections') {
        $dataDir = WIZARD_DATA_DIR;
        $profilesPath = WIZARD_PROFILES_FILE;
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $activeName = '';
        $configData = [];
        if (file_exists($activeProfilePath)) {
            $configData = json_decode(file_get_contents($activeProfilePath), true) ?: [];
            $activeName = $configData['active_connection']['name'] ?? '';
        }
        // Migrate old presets from active_profile.json into profiles.json (one-time migration)
        if (!empty($configData['presets'])) {
            $data = file_exists($profilesPath) ? (json_decode(file_get_contents($profilesPath), true) ?: []) : [];
            if (!is_array($data)) {
                $data = [];
            }
            foreach ($configData['presets'] as $p) {
                $pName = $p['name'] ?? '';
                if (!$pName) {
                    continue;
                }
                $exists = false;
                foreach ($data as $d) {
                    if (($d['name'] ?? '') === $pName) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $p['source'] = 'Preset';
                    if (!isset($p['env_file'])) {
                        $p['env_file'] = null;
                    }
                    $data[] = $p;
                }
            }
            file_put_contents($profilesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            unset($configData['presets']);
            file_put_contents($activeProfilePath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        // Read all connections from profiles.json (both Custom and Preset)
        $all = [];
        if (file_exists($profilesPath)) {
            $all = json_decode(file_get_contents($profilesPath), true) ?: [];
        }
        if (!is_array($all)) {
            $all = [];
        }
        $result = ['connections' => $all, 'active_name' => $activeName];
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode($result);
        exit;
    }

    if ($action === 'set_active_connection') {
        $connectionName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['connection_name'] ?? '');
        if (empty($connectionName)) {
            throw new \Exception('Nome da conexão é obrigatório.');
        }
        $dataDir = WIZARD_DATA_DIR;
        $profilesPath = WIZARD_PROFILES_FILE;
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;

        // Look in profiles.json (supports both Custom and Preset entries)
        $found = null;
        $data = file_exists($profilesPath) ? json_decode(file_get_contents($profilesPath), true) : [];
        if (is_array($data)) {
            foreach ($data as $c) {
                if (($c['name'] ?? $c['connection_name'] ?? '') === $connectionName) {
                    $found = $c;
                    break;
                }
            }
        }
        if (!$found) {
            throw new \Exception('Conexão não encontrada.');
        }
        $existingConfig = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        unset($existingConfig['presets']);
        $foundSource = $found['source'] ?? 'Custom';

        if ($foundSource === 'Custom') {
            $envFile = ltrim($found['env_file'] ?? $found['env'] ?? 'profiles/.' . $connectionName, './');
            $phpFile = ltrim($found['php_file'] ?? $found['test_file'] ?? 'profiles/' . $connectionName . '.php', './');
            $existingConfig['active_connection'] = [
                'source' => 'Custom',
                'name' => $connectionName,
                'module' => $found['module'] ?? 'Chainable',
                'engine' => $found['engine'] ?? '',
                'driver' => $found['driver'] ?? '',
                'type' => $found['type'] ?? $found['instance_type'] ?? 'Specific',
                'env_file' => $envFile,
                'php_file' => $phpFile,
            ];
        } else {
            $phpFile = ltrim($found['php_file'] ?? 'profiles/' . $connectionName . '.php', './');
            $existingConfig['active_connection'] = [
                'source' => 'Preset',
                'name' => $connectionName,
                'module' => $found['module'] ?? 'Chainable',
                'engine' => $found['engine'] ?? '',
                'driver' => $found['driver'] ?? '',
                'type' => $found['type'] ?? 'Specific',
                'php_file' => $phpFile,
            ];
        }
        file_put_contents($activeProfilePath, json_encode($existingConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Conexão definida como ativa.', 'config' => buildConfig()]);
        exit;
    }

    if ($action === 'clear_active_connection') {
        $dataDir = WIZARD_DATA_DIR;
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        unset($config['presets']);
        $config['active_connection'] = null;
        file_put_contents($activeProfilePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Conexão ativa removida.', 'config' => buildConfig()]);
        exit;
    }

    if ($action === 'delete_connection') {
        $connectionName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['connection_name'] ?? '');
        if (empty($connectionName)) {
            throw new \Exception('Nome da conexão é obrigatório.');
        }
        $dataDir = WIZARD_DATA_DIR;
        $profilesPath = WIZARD_PROFILES_FILE;
        $connectionsDir = WIZARD_PROFILES_DIR;
        if (!file_exists($profilesPath)) {
            throw new \Exception('Nenhuma conexão encontrada.');
        }
        $data = json_decode(file_get_contents($profilesPath), true);
        if (!is_array($data)) {
            throw new \Exception('Dados inválidos.');
        }
        // Find the entry to determine source before removing
        $found = null;
        foreach ($data as $c) {
            if (($c['name'] ?? $c['connection_name'] ?? '') === $connectionName) {
                $found = $c;
                break;
            }
        }
        $before = count($data);
        $data = array_values(array_filter($data, fn($c) => ($c['name'] ?? $c['connection_name'] ?? '') !== $connectionName));
        if (count($data) === $before) {
            throw new \Exception('Conexão não encontrada.');
        }
        file_put_contents($profilesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Source-aware file deletion: Preset only has PHP file; Custom has env + PHP
        $foundSource = $found['source'] ?? 'Custom';
        if ($foundSource === 'Custom') {
            $envFile = $connectionsDir . '/.' . $connectionName;
            if (file_exists($envFile)) {
                unlink($envFile);
            }
        }
        $phpFile = $connectionsDir . '/' . $connectionName . '.php';
        if (file_exists($phpFile)) {
            unlink($phpFile);
        }
        // Remove entries belonging to this connection from queries.json and query_history.json
        $queriesPath = WIZARD_QUERIES_FILE;
        if (file_exists($queriesPath)) {
            $qAll = json_decode(file_get_contents($queriesPath), true) ?: [];
            if (is_array($qAll)) {
                $qAll = array_values(array_filter($qAll, fn($q) => ($q['name'] ?? '') !== $connectionName));
                file_put_contents($queriesPath, json_encode($qAll, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        $historyPath = WIZARD_QUERY_HISTORY_FILE;
        if (file_exists($historyPath)) {
            $hAll = json_decode(file_get_contents($historyPath), true) ?: [];
            if (is_array($hAll)) {
                $hAll = array_values(array_filter($hAll, fn($h) => ($h['name'] ?? '') !== $connectionName));
                file_put_contents($historyPath, json_encode($hAll, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        foreach (['draft.json', 'builders.json', 'builder_history.json'] as $jsonFile) {
            $filePath = $dataDir . '/' . $jsonFile;
            if (file_exists($filePath)) {
                $fAll = json_decode(file_get_contents($filePath), true) ?: [];
                if (is_array($fAll)) {
                    $fAll = array_values(array_filter($fAll, fn($e) => ($e['name'] ?? '') !== $connectionName));
                    file_put_contents($filePath, json_encode($fAll, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
        }
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        if (file_exists($activeProfilePath)) {
            $config = json_decode(file_get_contents($activeProfilePath), true) ?: [];
            unset($config['presets']);
            if (($config['active_connection']['name'] ?? '') === $connectionName) {
                $config['active_connection'] = null;
            }
            file_put_contents($activeProfilePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Conexão excluída.', 'config' => buildConfig()]);
        exit;
    }

    if ($action === 'get_drivers') {
        $engine = $_GET['engine'] ?? '';
        if (empty($engine) || !isset($ENGINE_DRIVERS[$engine])) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            echo json_encode(['drivers' => []]);
            exit;
        }
        $drivers = $ENGINE_DRIVERS[$engine];
        $labels = [
            'mysql' => ($engine === 'native') ? 'MySQLi' : 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlsrv' => 'SQL Server',
            'oci' => 'Oracle',
            'firebird' => 'Firebird',
            'sqlite' => 'SQLite',
            'access' => 'Access',
            'excel' => 'Excel',
            'text' => 'Text',
            'csv' => 'CSV',
            'ini' => 'INI',
            'json' => 'JSON',
            'neon' => 'NEON',
            'xml' => 'XML',
            'yaml' => 'YAML',
        ];
        $result = [];
        foreach ($drivers as $d) {
            $result[] = ['id' => $d, 'label' => $labels[$d] ?? ucfirst($d)];
        }
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['drivers' => $result]);
        exit;
    }

    if ($action === 'get_connection_fields') {
        $driver = $_GET['driver'] ?? '';
        if (empty($driver) || !isset($DRIVER_ENV_KEYS[$driver])) {
            echo json_encode(['fields' => [], 'connData' => null]);
            exit;
        }
        $keys = $DRIVER_ENV_KEYS[$driver];
        $prefix = $DRIVER_ENV_PREFIX[$driver];
        $connectionName = $_GET['connection_name'] ?? '';
        $connData = null;
        if ($connectionName) {
            $env = loadEnvFromConnection($connectionName);
            $connData = buildConnectionDataFromEnv($env, $driver);
            if (!empty($connData['options'])) {
                $engine = $_GET['engine'] ?? 'pdo';
                $connData['options'] = normalizeOptionsForDisplay($connData['options'], $engine);
            }
        }
        $labels = [
            'HOST' => 'Host',
            'PORT' => 'Porta',
            'DATABASE' => 'Database/Caminho',
            'USERNAME' => 'Usuário',
            'PASSWORD' => 'Senha',
            'CHARSET' => 'Charset',
        ];
        $types = [
            'PASSWORD' => 'password',
            'PORT' => 'number',
        ];
        $result = [];
        foreach ($keys as $key) {
            $envKey = "{$prefix}_{$key}";
            $value = $connData ? ($connData[strtolower($key === 'USERNAME' ? 'user' : $key)] ?? '') : '';
            $result[] = [
                'name' => strtolower($key),
                'label' => $labels[$key] ?? $key,
                'env_key' => $envKey,
                'type' => $types[$key] ?? 'text',
                'value' => $value,
            ];
        }
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['fields' => $result, 'connData' => $connData]);
        exit;
    }

    if ($action === 'execute_query') {
        $queryType = $_POST['query_type'] ?? 'raw';
        $placeholderType = $_POST['placeholder_type'] ?? 'question';
        $sql = trim($_POST['sql'] ?? '');
        $params = $_POST['params'] ?? [];
        if (!is_array($params)) {
            $params = [];
        }

        if ($sql === '') {
            throw new \Exception('SQL query is required.');
        }

        $dataDir = WIZARD_DATA_DIR;
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        if (empty($config['active_connection'])) {
            throw new \Exception('No active connection configured.');
        }

        $conn = buildConnectionForQuery($config);

        $startTime = microtime(true);
        $paramValues = array_values($params);
        if ($queryType === 'prepared' && !empty($paramValues)) {
            // Native connection prepare(): handles `:name` → `?` conversion internally via Parse::binding()
            $conn->prepare($sql, ...$paramValues);
        } else {
            $conn->query($sql);
        }
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $columnCount = $conn->getQueryColumns();
        $isSelect = $columnCount > 0;

        $result = ['success' => true, 'duration_ms' => $duration, 'query_type' => $queryType];
        if ($isSelect) {
            $rows = $conn->fetchAll(2); // 2 = FETCH_ASSOC across all engine connections
            if (!is_array($rows)) {
                $rows = [];
            }
            $result['rows'] = $rows;
            $result['count'] = count($rows);
            $result['columns'] = !empty($rows) ? array_keys($rows[0]) : [];
        } else {
            $result['affected_rows'] = (int) $conn->getAffectedRows();
        }

        // Persist to history
        $activeConnName = $config['active_connection']['name'] ?? '';
        $historyPath = WIZARD_QUERY_HISTORY_FILE;
        $history = file_exists($historyPath) ? (json_decode(file_get_contents($historyPath), true) ?: []) : [];
        if (!is_array($history)) {
            $history = [];
        }
        array_unshift($history, [
            'id' => uniqid('qh_', true),
            'name' => $activeConnName,
            'sql' => $sql,
            'params' => array_values($params),
            'query_type' => $queryType,
            'placeholder_type' => $placeholderType,
            'executed_at' => date('Y-m-d H:i:s'),
            'duration_ms' => $duration,
            'row_count' => $isSelect ? $result['count'] : ($result['affected_rows'] ?? 0),
        ]);
        file_put_contents($historyPath, json_encode(array_slice($history, 0, 100), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode($result);
        exit;
    }

    if ($action === 'save_query') {
        $title = trim($_POST['name'] ?? '');
        $sql = trim($_POST['sql'] ?? '');
        $queryType = $_POST['query_type'] ?? 'raw';
        $placeholderType = $_POST['placeholder_type'] ?? 'question';
        $params = $_POST['params'] ?? [];
        if (!is_array($params)) {
            $params = [];
        }
        if ($title === '') {
            throw new \Exception('Query name is required.');
        }
        if ($sql === '') {
            throw new \Exception('SQL query is required.');
        }
        $connectionName = getActiveConnectionName();
        if (!$connectionName) {
            throw new \Exception('No active connection configured.');
        }
        $queriesPath = WIZARD_QUERIES_FILE;
        $queries = file_exists($queriesPath) ? (json_decode(file_get_contents($queriesPath), true) ?: []) : [];
        if (!is_array($queries)) {
            $queries = [];
        }
        $found = false;
        foreach ($queries as &$q) {
            if (($q['title'] ?? $q['name'] ?? '') === $title && ($q['name'] ?? '') === $connectionName) {
                $q['name'] = $connectionName;
                $q['title'] = $title;
                $q['sql'] = $sql;
                $q['query_type'] = $queryType;
                $q['placeholder_type'] = $placeholderType;
                $q['params'] = array_values($params);
                $q['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        unset($q);
        if (!$found) {
            $queries[] = [
                'id' => uniqid('q_', true),
                'name' => $connectionName,
                'title' => $title,
                'sql' => $sql,
                'query_type' => $queryType,
                'placeholder_type' => $placeholderType,
                'params' => array_values($params),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        file_put_contents($queriesPath, json_encode($queries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Return only queries for the active connection
        $filtered = array_values(array_filter($queries, fn($q) => ($q['name'] ?? '') === $connectionName));
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'queries' => $filtered]);
        exit;
    }

    if ($action === 'get_queries') {
        $connectionName = getActiveConnectionName();
        $queries = [];
        if ($connectionName) {
            $queriesPath = WIZARD_QUERIES_FILE;
            $all = file_exists($queriesPath) ? (json_decode(file_get_contents($queriesPath), true) ?: []) : [];
            if (!is_array($all)) {
                $all = [];
            }
            $queries = array_values(array_filter($all, fn($q) => ($q['name'] ?? '') === $connectionName));
        }
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'queries' => $queries]);
        exit;
    }

    if ($action === 'delete_query') {
        $queryId = $_POST['query_id'] ?? '';
        if ($queryId === '') {
            throw new \Exception('Query ID is required.');
        }
        $connectionName = getActiveConnectionName();
        $queriesPath = WIZARD_QUERIES_FILE;
        $all = file_exists($queriesPath) ? (json_decode(file_get_contents($queriesPath), true) ?: []) : [];
        if (!is_array($all)) {
            $all = [];
        }
        $before = count($all);
        $all = array_values(array_filter($all, fn($q) => ($q['id'] ?? '') !== $queryId));
        if (count($all) === $before) {
            throw new \Exception('Query not found.');
        }
        file_put_contents($queriesPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Return only queries for the active connection
        $filtered = $connectionName
            ? array_values(array_filter($all, fn($q) => ($q['name'] ?? '') === $connectionName))
            : [];
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'queries' => $filtered]);
        exit;
    }

    if ($action === 'get_query_history') {
        $connectionName = getActiveConnectionName();
        $history = [];
        if ($connectionName) {
            $historyPath = WIZARD_QUERY_HISTORY_FILE;
            $all = file_exists($historyPath) ? (json_decode(file_get_contents($historyPath), true) ?: []) : [];
            if (!is_array($all)) {
                $all = [];
            }
            $history = array_values(array_filter($all, fn($h) => ($h['name'] ?? '') === $connectionName));
        }
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    if ($action === 'save_draft') {
        $alias = trim($_POST['alias'] ?? '');
        $sql = trim($_POST['sql'] ?? '');
        $source = in_array($_POST['source'] ?? '', ['query', 'builder']) ? $_POST['source'] : 'query';
        $qbCode = trim($_POST['qb_code'] ?? '');
        $queryType = $_POST['query_type'] ?? 'raw';
        $placeholderType = $_POST['placeholder_type'] ?? 'question';
        $params = $_POST['params'] ?? [];
        if (!is_array($params)) {
            $params = [];
        }
        if ($sql === '') {
            throw new \Exception('SQL query is required.');
        }
        $connectionName = getActiveConnectionName();
        if (!$connectionName) {
            throw new \Exception('No active connection configured.');
        }
        if ($alias === '') {
            $alias = $connectionName . '_' . substr(md5($sql), 0, 6);
        }
        $draftPath = WIZARD_DRAFT_FILE;
        $drafts = file_exists($draftPath) ? (json_decode(file_get_contents($draftPath), true) ?: []) : [];
        if (!is_array($drafts)) {
            $drafts = [];
        }
        $found = false;
        foreach ($drafts as &$d) {
            if (($d['alias'] ?? '') === $alias && ($d['name'] ?? '') === $connectionName && ($d['source'] ?? 'query') === $source) {
                $d['sql'] = $sql;
                $d['source'] = $source;
                if ($source === 'builder') {
                    $d['qb_code'] = $qbCode;
                } else {
                    $d['query_type'] = $queryType;
                    $d['placeholder_type'] = $placeholderType;
                    $d['params'] = array_values($params);
                }
                $d['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        unset($d);
        if (!$found) {
            $entry = [
                'id' => uniqid('d_', true),
                'name' => $connectionName,
                'alias' => $alias,
                'source' => $source,
                'is_draft' => true,
                'sql' => $sql,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($source === 'builder') {
                $entry['qb_code'] = $qbCode;
            } else {
                $entry['query_type'] = $queryType;
                $entry['placeholder_type'] = $placeholderType;
                $entry['params'] = array_values($params);
            }
            $drafts[] = $entry;
        }
        file_put_contents($draftPath, json_encode($drafts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filtered = array_values(array_filter($drafts, fn($d) => ($d['name'] ?? '') === $connectionName && ($d['source'] ?? 'query') === $source));
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'drafts' => $filtered]);
        exit;
    }

    if ($action === 'get_drafts') {
        $connectionName = getActiveConnectionName();
        $sourceFilter = $_GET['source'] ?? null;
        $drafts = [];
        if ($connectionName) {
            $draftPath = WIZARD_DRAFT_FILE;
            $all = file_exists($draftPath) ? (json_decode(file_get_contents($draftPath), true) ?: []) : [];
            if (!is_array($all)) {
                $all = [];
            }
            $drafts = array_values(array_filter($all, function ($d) use ($connectionName, $sourceFilter) {
                if (($d['name'] ?? '') !== $connectionName) return false;
                if ($sourceFilter !== null && ($d['source'] ?? 'query') !== $sourceFilter) return false;
                return true;
            }));
        }
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'drafts' => $drafts]);
        exit;
    }

    if ($action === 'delete_draft') {
        $draftId = $_POST['draft_id'] ?? '';
        if ($draftId === '') {
            throw new \Exception('Draft ID is required.');
        }
        $connectionName = getActiveConnectionName();
        $draftPath = WIZARD_DRAFT_FILE;
        $all = file_exists($draftPath) ? (json_decode(file_get_contents($draftPath), true) ?: []) : [];
        if (!is_array($all)) {
            $all = [];
        }
        $all = array_values(array_filter($all, fn($d) => ($d['id'] ?? '') !== $draftId));
        file_put_contents($draftPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filtered = array_values(array_filter($all, fn($d) => ($d['name'] ?? '') === $connectionName));
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'drafts' => $filtered]);
        exit;
    }

    if ($action === 'convert_to_qb') {
        $sql = trim($_POST['sql'] ?? '');
        if ($sql === '') {
            if (ob_get_level()) ob_end_clean();
            echo json_encode(['success' => true, 'qb_code' => '']);
            exit;
        }
        $dataDir = WIZARD_DATA_DIR;
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        $activeEngineRaw = $config['active_connection']['engine'] ?? '';
        $activeDriverRaw = $config['active_connection']['driver'] ?? '';
        $activeEngine = fromEngineLabel($activeEngineRaw);
        $activeDriver = fromDriverLabel($activeDriverRaw, $activeEngine);
        $qbClassFull = (!empty($activeEngine) && !empty($activeDriver))
            ? getQueryBuilderClass($activeEngine, $activeDriver)
            : '';
        $qbClassShort = $qbClassFull !== '' ? basename(str_replace('\\', '/', $qbClassFull)) : '';
        $qbCode = sqlToQbCode($sql, $qbClassShort);
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'qb_code' => $qbCode]);
        exit;
    }

    if ($action === 'execute_builder') {
        $sql = trim($_POST['sql'] ?? '');
        $qbCode = trim($_POST['qb_code'] ?? '');
        if ($sql === '') {
            throw new \Exception('SQL query is required.');
        }
        $dataDir = WIZARD_DATA_DIR;
        $activeProfilePath = WIZARD_ACTIVE_PROFILE_FILE;
        $config = file_exists($activeProfilePath) ? (json_decode(file_get_contents($activeProfilePath), true) ?: []) : [];
        if (empty($config['active_connection'])) {
            throw new \Exception('No active connection configured.');
        }
        $conn = buildConnectionForQuery($config);
        $startTime = microtime(true);
        $conn->query($sql);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $columnCount = $conn->getQueryColumns();
        $isSelect = $columnCount > 0;
        $result = ['success' => true, 'duration_ms' => $duration];
        if ($isSelect) {
            $rows = $conn->fetchAll(2); // 2 = FETCH_ASSOC
            if (!is_array($rows)) {
                $rows = [];
            }
            $result['rows'] = $rows;
            $result['count'] = count($rows);
            $result['columns'] = !empty($rows) ? array_keys($rows[0]) : [];
        } else {
            $result['affected_rows'] = (int) $conn->getAffectedRows();
        }
        $activeConnName = $config['active_connection']['name'] ?? '';
        $histPath = WIZARD_BUILDER_HISTORY_FILE;
        $hist = file_exists($histPath) ? (json_decode(file_get_contents($histPath), true) ?: []) : [];
        if (!is_array($hist)) $hist = [];
        array_unshift($hist, [
            'id' => uniqid('bh_', true),
            'name' => $activeConnName,
            'sql' => $sql,
            'qb_code' => $qbCode,
            'executed_at' => date('Y-m-d H:i:s'),
            'duration_ms' => $duration,
            'row_count' => $isSelect ? $result['count'] : ($result['affected_rows'] ?? 0),
        ]);
        file_put_contents($histPath, json_encode(array_slice($hist, 0, 100), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (ob_get_level()) ob_end_clean();
        echo json_encode($result);
        exit;
    }

    if ($action === 'save_builder') {
        $sql = trim($_POST['sql'] ?? '');
        $qbCode = trim($_POST['qb_code'] ?? '');
        if ($sql === '') {
            throw new \Exception('SQL is required.');
        }
        $connectionName = getActiveConnectionName();
        if (!$connectionName) {
            throw new \Exception('No active connection configured.');
        }
        $title = 'build_' . time();
        $buildersPath = WIZARD_BUILDERS_FILE;
        $builders = file_exists($buildersPath) ? (json_decode(file_get_contents($buildersPath), true) ?: []) : [];
        if (!is_array($builders)) $builders = [];
        $builders[] = [
            'id' => uniqid('b_', true),
            'name' => $connectionName,
            'title' => $title,
            'sql' => $sql,
            'qb_code' => $qbCode,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents($buildersPath, json_encode($builders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filtered = array_values(array_filter($builders, fn($b) => ($b['name'] ?? '') === $connectionName));
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'builders' => $filtered]);
        exit;
    }

    if ($action === 'get_builders') {
        $connectionName = getActiveConnectionName();
        $builders = [];
        if ($connectionName) {
            $buildersPath = WIZARD_BUILDERS_FILE;
            $all = file_exists($buildersPath) ? (json_decode(file_get_contents($buildersPath), true) ?: []) : [];
            if (!is_array($all)) $all = [];
            $builders = array_values(array_filter($all, fn($b) => ($b['name'] ?? '') === $connectionName));
        }
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'builders' => $builders]);
        exit;
    }

    if ($action === 'delete_builder') {
        $builderId = $_POST['builder_id'] ?? '';
        if ($builderId === '') {
            throw new \Exception('Builder ID is required.');
        }
        $connectionName = getActiveConnectionName();
        $buildersPath = WIZARD_BUILDERS_FILE;
        $all = file_exists($buildersPath) ? (json_decode(file_get_contents($buildersPath), true) ?: []) : [];
        if (!is_array($all)) $all = [];
        $all = array_values(array_filter($all, fn($b) => ($b['id'] ?? '') !== $builderId));
        file_put_contents($buildersPath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filtered = array_values(array_filter($all, fn($b) => ($b['name'] ?? '') === $connectionName));
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'builders' => $filtered]);
        exit;
    }

    if ($action === 'get_builder_history') {
        $connectionName = getActiveConnectionName();
        $history = [];
        if ($connectionName) {
            $histPath = WIZARD_BUILDER_HISTORY_FILE;
            $all = file_exists($histPath) ? (json_decode(file_get_contents($histPath), true) ?: []) : [];
            if (!is_array($all)) $all = [];
            $history = array_values(array_filter($all, fn($h) => ($h['name'] ?? '') === $connectionName));
        }
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    if ($action === 'get_settings') {
        $config = buildConfig();
        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'settings' => $config['settings']]);
        exit;
    }

    if ($action === 'update_settings') {
        $paginationKeys = ['queries_page_size', 'history_page_size', 'connections_page_size', 'builders_page_size', 'builder_history_page_size'];
        $pathKeys       = ['profiles_dir', 'active_profile_file', 'profiles_file', 'queries_file', 'query_history_file', 'builders_file', 'builder_history_file', 'draft_file'];

        // Start from current TOML so unmodified keys are preserved.
        $toml = read_toml_file(WIZARD_SETTINGS_FILE);

        foreach ($paginationKeys as $key) {
            if (isset($_POST[$key])) {
                $val = (int) $_POST[$key];
                if ($val >= 1 && $val <= 100) {
                    $toml[$key] = $val;
                }
            }
        }

        foreach ($pathKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                // Must start with / and must not contain path traversal sequences.
                if (str_starts_with($val, '/') && strpos($val, '..') === false) {
                    $toml[$key] = $val;
                }
            }
        }

        write_toml_file(WIZARD_SETTINGS_FILE, $toml);

        if (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => true, 'settings' => $toml]);
        exit;
    }

    if (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode(['error' => 'Ação não reconhecida']);
} catch (\Throwable $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
