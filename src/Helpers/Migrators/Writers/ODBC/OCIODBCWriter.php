<?php

namespace GenericDatabase\Helpers\Migrators\Writers\ODBC;

use GenericDatabase\Helpers\Migrators\Writers\BaseODBCWriter;

/**
 * Native ODBC target writer for Oracle.
 *
 * Uses ext-odbc (odbc_connect / odbc_exec / odbc_prepare / odbc_execute) directly.
 * No PDO dependency.
 *
 * Oracle specifics:
 *  - No CREATE DATABASE: target schema/user must already exist (no-op).
 *  - CREATE TABLE is wrapped in a PL/SQL EXECUTE IMMEDIATE block for IF-NOT-EXISTS semantics.
 *  - Date/Timestamp columns use TO_TIMESTAMP(?, 'YYYY-MM-DD HH24:MI:SS') placeholders.
 *  - All string values are coerced to UTF-8.
 *  - Table existence is checked via USER_TABLES.
 *
 * Required $params keys:
 *   host, port, user, password
 *   service (optional)         — Oracle service name / SID (default: $dbName)
 *   odbc_driver (optional)     — default: 'Oracle in OraClient21Home1'
 */
class OCIODBCWriter extends BaseODBCWriter
{
    public function getTargetEngine(): string
    {
        return 'oracle';
    }

    public function escapeIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', strtoupper($name)) . '"';
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($canonical) {
            'bigint', 'integer'     => 'NUMBER(10)',
            'smallint'              => 'NUMBER(5)',
            'boolean'               => 'NUMBER(1)',
            'float'                 => 'BINARY_FLOAT',
            'double'                => 'BINARY_DOUBLE',
            'decimal'               => 'NUMBER(' . $this->extractSize($rawType, '10,2') . ')',
            'date'                  => 'DATE',
            'time'                  => 'TIMESTAMP',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'blob'                  => 'BLOB',
            'clob', 'text'          => 'CLOB',
            'char'                  => 'CHAR(' . $this->extractSize($rawType, '1') . ')',
            'varchar'               => 'VARCHAR2(' . $this->extractSize($rawType, '255') . ')',
            default                 => 'VARCHAR2(4000)',
        };
    }

    /**
     * Oracle does not support CREATE DATABASE via SQL.
     * Target schema/user must already exist.
     */
    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // No-op for Oracle.
    }

    protected function createODBCConnection(string $dbName, array $params): mixed
    {
        $driver   = $params['odbc_driver'] ?? 'Oracle in OraClient21Home1';
        $host     = $params['host'];
        $port     = $params['port'];
        $service  = $params['service'] ?? $dbName;
        $user     = $params['user'];
        $password = $params['password'];

        $dsn = "Driver={$driver};DBQ=//{$host}:{$port}/{$service};";

        return odbc_connect($dsn, $user, $password);
    }

    // -----------------------------------------------------------------------
    // Oracle: table existence via USER_TABLES
    // -----------------------------------------------------------------------

    public function tableExists(mixed $conn, string $table): bool
    {
        $stmt = @odbc_exec(
            $conn,
            "SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME = UPPER('$table')"
        );
        if ($stmt === false) {
            return false;
        }
        $row   = odbc_fetch_array($stmt);
        $count = $row ? (int)current($row) : 0;
        odbc_free_result($stmt);
        return $count > 0;
    }

    // -----------------------------------------------------------------------
    // Oracle: TO_TIMESTAMP placeholders for date/time columns
    // -----------------------------------------------------------------------

    protected function buildPlaceholders(array $columns, array $schema): string
    {
        $schemaByName = [];
        foreach ($schema['columns'] as $col) {
            $schemaByName[$col['name']] = $col;
        }

        $placeholders = [];
        foreach ($columns as $colName) {
            $canonical      = $schemaByName[$colName]['canonical'] ?? 'text';
            $placeholders[] = in_array($canonical, ['date', 'time', 'datetime', 'timestamp'], true)
                ? "TO_TIMESTAMP(?, 'YYYY-MM-DD HH24:MI:SS')"
                : '?';
        }

        return implode(', ', $placeholders);
    }

    protected function prepareRowValues(array $row, array $columns, array $schema): array
    {
        $schemaByName = [];
        foreach ($schema['columns'] as $col) {
            $schemaByName[$col['name']] = $col;
        }

        $values = [];
        foreach ($columns as $colName) {
            $value     = $row[$colName] ?? null;
            $canonical = $schemaByName[$colName]['canonical'] ?? 'text';

            if ($value === '' || $value === null) {
                $values[] = null;
                continue;
            }

            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            }

            if (in_array($canonical, ['date', 'time', 'datetime', 'timestamp'], true)) {
                $value = $this->normaliseDateValue($value);
            }

            $values[] = $value !== null ? (string)$value : null;
        }

        return $values;
    }

    private function normaliseDateValue(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            if (is_numeric($value) && (int)$value > 0) {
                return date('Y-m-d H:i:s', (int)$value);
            }
            return null;
        }

        $value = trim($value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}):(\d{2}))?/', $value, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                $h = str_pad($m[4] ?? '00', 2, '0', STR_PAD_LEFT);
                $i = str_pad($m[5] ?? '00', 2, '0', STR_PAD_LEFT);
                $s = str_pad($m[6] ?? '00', 2, '0', STR_PAD_LEFT);
                return "{$m[1]}-{$m[2]}-{$m[3]} $h:$i:$s";
            }
        }

        $ts = @strtotime($value);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }
}
