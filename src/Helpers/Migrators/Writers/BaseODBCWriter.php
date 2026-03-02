<?php

namespace GenericDatabase\Helpers\Migrators\Writers;

use GenericDatabase\Helpers\Migrators\MigrationResult;
use GenericDatabase\Helpers\Migrators\Readers\BaseMigrator;
use Exception;
use RuntimeException;

/**
 * Base abstract class for all native-ODBC target writers.
 *
 * Uses PHP's ext-odbc functions (odbc_connect, odbc_exec, odbc_prepare,
 * odbc_execute) directly — no PDO dependency.
 *
 * Subclasses implement engine-specific:
 *   - Type conversion  (canonical → target SQL type)
 *   - Identifier quoting
 *   - Database creation (or no-op)
 *   - ODBC connection setup via createODBCConnection()
 *
 * The shared migrate() template drives the full migration loop:
 * create DB → connect → for each table: create table + insert rows.
 *
 * Error handling: odbc_* functions return false on failure; errors are read
 * via odbc_errormsg() and converted to exceptions or MigrationResult errors.
 *
 * Parameter binding: odbc_execute() receives a plain array; null values are
 * sent as SQL NULL, all other values are cast to string.
 */
abstract class BaseODBCWriter
{
    /**
     * Short lowercase identifier for this target engine.
     */
    abstract public function getTargetEngine(): string;

    /**
     * Quote a table or column identifier for the target engine.
     */
    abstract public function escapeIdentifier(string $name): string;

    /**
     * Convert a canonical type and raw source type to the target SQL type.
     */
    abstract protected function convertType(string $canonical, string $rawType): string;

    /**
     * Create the target database if it does not already exist.
     * For engines where this concept does not apply this is a no-op.
     */
    abstract protected function createDatabaseIfNotExists(string $dbName, array $params): void;

    /**
     * Return a ready-to-use native ODBC connection to the target database.
     * Returns the connection resource/object, or false on failure.
     *
     * @return mixed ODBC connection resource / ODBCConnection object
     */
    abstract protected function createODBCConnection(string $dbName, array $params): mixed;

    // -----------------------------------------------------------------------
    // Type helpers
    // -----------------------------------------------------------------------

    protected function extractSize(string $rawType, string $default = '255'): string
    {
        if (preg_match('/\(([^)]+)\)/', $rawType, $m)) {
            return trim($m[1]);
        }
        return $default;
    }

    // -----------------------------------------------------------------------
    // DDL generation  (mirrors BaseWriter logic)
    // -----------------------------------------------------------------------

    public function generateCreateTableSQL(string $table, array $schema): string
    {
        $tableEsc = $this->escapeIdentifier($table);
        $engine   = $this->getTargetEngine();

        $prefix = ($engine === 'oracle' || $engine === 'sqlsrv')
            ? "CREATE TABLE $tableEsc (\n"
            : "CREATE TABLE IF NOT EXISTS $tableEsc (\n";

        $colDefs     = [];
        $primaryKeys = [];

        foreach ($schema['columns'] as $col) {
            $colEsc  = $this->escapeIdentifier($col['name']);
            $colType = $this->convertType($col['canonical'], $col['raw_type']);
            $def     = "  $colEsc $colType";

            if ($col['notnull']) {
                $def .= ' NOT NULL';
            }

            if ($col['dflt_value'] !== null) {
                $defaultExpr = $this->formatDefaultValue($col['dflt_value'], $col['canonical']);
                if ($defaultExpr !== null) {
                    $def .= " DEFAULT $defaultExpr";
                }
            }

            $colDefs[] = $def;

            if ($col['pk']) {
                $primaryKeys[] = $col['name'];
            }
        }

        if (!empty($primaryKeys)) {
            $pkEsc     = array_map([$this, 'escapeIdentifier'], $primaryKeys);
            $colDefs[] = '  PRIMARY KEY (' . implode(', ', $pkEsc) . ')';
        }

        $body = implode(",\n", $colDefs);

        if ($engine === 'mysql') {
            return $prefix . $body . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        if ($engine === 'oracle') {
            $rawSQL     = $prefix . $body . "\n)";
            $escapedSQL = str_replace("'", "''", $rawSQL);
            return "BEGIN\n"
                . "  EXECUTE IMMEDIATE '$escapedSQL';\n"
                . "EXCEPTION\n"
                . "  WHEN OTHERS THEN\n"
                . "    IF SQLCODE = -955 THEN NULL;\n"
                . "    ELSE RAISE;\n"
                . "    END IF;\n"
                . "END;";
        }

        return $prefix . $body . "\n)";
    }

    protected function formatDefaultValue(string $raw, string $canonical): ?string
    {
        if (
            strlen($raw) >= 2
            && (($raw[0] === "'" && str_ends_with($raw, "'"))
                || ($raw[0] === '"' && str_ends_with($raw, '"')))
        ) {
            $raw = substr($raw, 1, -1);
        }

        $numericCanonicals = ['integer', 'bigint', 'smallint', 'float', 'double', 'decimal', 'boolean'];

        if (in_array($canonical, $numericCanonicals, true)) {
            return is_numeric($raw) ? $raw : null;
        }

        return "'" . str_replace("'", "''", $raw) . "'";
    }

    // -----------------------------------------------------------------------
    // Table existence check
    // -----------------------------------------------------------------------

    /**
     * Check if a table exists using odbc_tables() (standard ODBC metadata).
     * Override in engine-specific subclasses when odbc_tables() is unreliable.
     *
     * @param mixed $conn ODBC connection resource/object
     */
    public function tableExists(mixed $conn, string $table): bool
    {
        try {
            $result = @odbc_tables($conn, '', '', $table, 'TABLE');
            if ($result === false) {
                return false;
            }
            $exists = odbc_fetch_row($result) !== false;
            odbc_free_result($result);
            return $exists;
        } catch (Exception) {
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // DML helpers
    // -----------------------------------------------------------------------

    protected function buildInsertSQL(string $table, array $columns, array $schema): string
    {
        $tableEsc     = $this->escapeIdentifier($table);
        $colsEsc      = implode(', ', array_map([$this, 'escapeIdentifier'], $columns));
        $placeholders = $this->buildPlaceholders($columns, $schema);
        return "INSERT INTO $tableEsc ($colsEsc) VALUES ($placeholders)";
    }

    /**
     * Build the VALUES placeholder string.
     * Override in subclasses that need function-wrapped placeholders (e.g. Oracle TO_TIMESTAMP).
     */
    protected function buildPlaceholders(array $columns, array $schema): string
    {
        return implode(', ', array_fill(0, count($columns), '?'));
    }

    /**
     * Prepare row values for odbc_execute().
     * null → SQL NULL; all other values cast to string.
     * Override for engine-specific value transformation.
     *
     * @param array<string, mixed> $row
     * @param string[]             $columns
     * @return list<string|null>
     */
    protected function prepareRowValues(array $row, array $columns, array $schema): array
    {
        $values = [];
        foreach ($columns as $colName) {
            $v = $row[$colName] ?? null;
            if ($v === null || $v === '') {
                $values[] = null;
            } else {
                $values[] = (string)$v;
            }
        }
        return $values;
    }

    // -----------------------------------------------------------------------
    // Error classification
    // -----------------------------------------------------------------------

    protected function isTableAlreadyExistsError(string $message): bool
    {
        return str_contains($message, 'already exists')
            || str_contains($message, 'Duplicate table')
            || str_contains($message, 'ORA-00955')
            || str_contains($message, 'There is already an object')
            || (str_contains($message, 'table') && str_contains($message, 'exists'));
    }

    // -----------------------------------------------------------------------
    // Migration template
    // -----------------------------------------------------------------------

    /**
     * Run the full migration from $reader to this target engine using native ODBC.
     *
     * @param BaseMigrator         $reader Source reader
     * @param string               $dbName Target database/schema name
     * @param array<string, mixed> $params Connection parameters
     */
    public function migrate(BaseMigrator $reader, string $dbName, array $params): MigrationResult
    {
        $result = new MigrationResult($reader->getSourceEngine(), $this->getTargetEngine(), $dbName);

        $conn = null;
        try {
            $this->createDatabaseIfNotExists($dbName, $params);
            $conn = $this->createODBCConnection($dbName, $params);
            if ($conn === false) {
                throw new RuntimeException('ODBC connection failed: ' . odbc_errormsg());
            }
        } catch (Exception $e) {
            $result->setGlobalError($e->getMessage());
            return $result;
        }

        foreach ($reader->getTables() as $table) {
            try {
                $schema   = $reader->getTableSchema($table);
                $tableEsc = $this->escapeIdentifier($table);

                // Idempotency: skip tables that already have data
                if ($this->tableExists($conn, $table)) {
                    $countRes = @odbc_exec($conn, "SELECT COUNT(*) FROM $tableEsc");
                    if ($countRes !== false) {
                        $row = odbc_fetch_array($countRes);
                        $existingCount = $row ? (int)current($row) : 0;
                        odbc_free_result($countRes);
                        if ($existingCount > 0) {
                            $result->addSkipped($table, $existingCount);
                            continue;
                        }
                    }
                } else {
                    $createSQL = $this->generateCreateTableSQL($table, $schema);
                    $createRes = @odbc_exec($conn, $createSQL);
                    if ($createRes === false) {
                        $err = odbc_errormsg($conn);
                        if (!$this->isTableAlreadyExistsError($err)) {
                            $result->addError($table, 'CREATE TABLE: ' . $err);
                            continue;
                        }
                    }
                }

                // Migrate rows
                $rows = $reader->getTableData($table);
                if (empty($rows)) {
                    $result->addMigrated($table, 0);
                    continue;
                }

                $columns   = array_keys($rows[0]);
                $insertSQL = $this->buildInsertSQL($table, $columns, $schema);
                $stmt      = @odbc_prepare($conn, $insertSQL);

                if ($stmt === false) {
                    $result->addError($table, 'PREPARE INSERT: ' . odbc_errormsg($conn));
                    continue;
                }

                $count = 0;
                foreach ($rows as $row) {
                    $values = $this->prepareRowValues($row, $columns, $schema);
                    if (@odbc_execute($stmt, $values)) {
                        $count++;
                    }
                }

                odbc_free_result($stmt);
                $result->addMigrated($table, $count);

            } catch (Exception $e) {
                $result->addError($table, $e->getMessage());
            }
        }

        if ($conn !== false && $conn !== null) {
            odbc_close($conn);
        }

        return $result;
    }
}
