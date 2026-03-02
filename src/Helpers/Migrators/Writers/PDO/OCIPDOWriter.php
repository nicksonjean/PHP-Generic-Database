<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO target writer for Oracle (via PDO OCI driver).
 *
 * Oracle specifics:
 *  - No CREATE DATABASE: Oracle uses schemas/users — createDatabaseIfNotExists is a no-op.
 *  - PL/SQL EXECUTE IMMEDIATE block wraps CREATE TABLE for IF-NOT-EXISTS semantics.
 *  - Date/Timestamp columns use TO_TIMESTAMP(?, 'YYYY-MM-DD HH24:MI:SS') placeholders.
 *  - All string values are coerced to UTF-8.
 *  - NLS session settings are applied after connecting.
 */
class OCIPDOWriter extends BasePDOWriter
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
        // No-op for Oracle: the schema/user must be pre-created by a DBA.
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $host    = $params['host'];
        $port    = $params['port'];
        $service = $params['service'] ?? $dbName;
        $charset = $params['charset'] ?? 'AL32UTF8';
        $connStr = "//$host:$port/$service";

        $conn = null;
        foreach (
            [
                "oci:dbname=$connStr;charset=$charset",
                "oci:dbname=$connStr",
            ] as $dsn
        ) {
            try {
                $conn = new PDO($dsn, $params['user'], $params['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                break;
            } catch (PDOException) {
                // try next format
            }
        }

        if ($conn === null) {
            throw new RuntimeException("Oracle connection failed for {$connStr}");
        }

        foreach (
            [
                "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'",
                "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'",
                "ALTER SESSION SET NLS_LANGUAGE = 'AMERICAN'",
                "ALTER SESSION SET NLS_TERRITORY = 'AMERICA'",
            ] as $nlsSQL
        ) {
            try {
                $conn->exec($nlsSQL);
            } catch (PDOException) {
                // Ignore: may lack ALTER SESSION privilege
            }
        }

        return $conn;
    }

    // -----------------------------------------------------------------------
    // Oracle-specific INSERT placeholders (TO_TIMESTAMP for date columns)
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

            $values[] = $value;
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
