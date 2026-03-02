<?php

namespace GenericDatabase\Helpers\Migrators\Writers;

use GenericDatabase\Helpers\Migrators\MigrationResult;
use GenericDatabase\Helpers\Migrators\Readers\BaseMigrator;
use PDO;
use PDOException;
use Exception;

/**
 * Base abstract class for all target database writers used in migration.
 *
 * Subclasses implement engine-specific:
 *   - Type conversion  (canonical → target SQL type)
 *   - Identifier quoting
 *   - Database creation
 *   - PDO connection setup
 *
 * The shared migrate() template drives the full migration loop:
 * create DB → connect → for each table: create table + insert rows.
 */
abstract class BasePDOWriter
{
    /**
     * Short lowercase identifier for this target engine.
     * Examples: 'mysql', 'pgsql', 'oracle', 'sqlsrv', 'firebird', 'sqlite'.
     */
    abstract public function getTargetEngine(): string;

    /**
     * Quote a table or column identifier for the target engine.
     */
    abstract public function escapeIdentifier(string $name): string;

    /**
     * Convert a canonical type (from BaseMigrator::normalizeToCanonical) and the
     * raw source type string to the appropriate target SQL type.
     *
     * @param string $canonical Canonical type identifier (e.g. 'varchar', 'integer')
     * @param string $rawType   Raw source type string (e.g. 'VARCHAR(255)')
     * @return string           Target SQL type string
     */
    abstract protected function convertType(string $canonical, string $rawType): string;

    /**
     * Create the target database if it does not already exist.
     * For engines where this concept does not apply (Oracle, Firebird, SQLite) this is a no-op.
     *
     * @param string               $dbName Target database name
     * @param array<string, mixed> $params Connection parameters
     */
    abstract protected function createDatabaseIfNotExists(string $dbName, array $params): void;

    /**
     * Return a ready-to-use PDO connection to the target database.
     *
     * @param string               $dbName Target database name
     * @param array<string, mixed> $params Connection parameters
     */
    abstract protected function createConnection(string $dbName, array $params): PDO;

    // -----------------------------------------------------------------------
    // Type helpers
    // -----------------------------------------------------------------------

    /**
     * Extract a size specifier from a raw type string or return a default.
     *
     * @param string $rawType  e.g. "VARCHAR(255)" or "DECIMAL(10,2)"
     * @param string $default  Fallback if no size found
     */
    protected function extractSize(string $rawType, string $default = '255'): string
    {
        if (preg_match('/\(([^)]+)\)/', $rawType, $m)) {
            return trim($m[1]);
        }
        return $default;
    }

    // -----------------------------------------------------------------------
    // DDL generation
    // -----------------------------------------------------------------------

    /**
     * Generate a CREATE TABLE statement for the target engine.
     */
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

    /**
     * Format a column default value as a SQL literal, or null to omit DEFAULT.
     */
    protected function formatDefaultValue(string $raw, string $canonical): ?string
    {
        // Strip surrounding quotes from source schema
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

    public function tableExists(PDO $conn, string $table): bool
    {
        $engine = $this->getTargetEngine();
        try {
            $sql = match ($engine) {
                'mysql'   => 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()',
                'pgsql'   => "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ? AND table_schema = 'public'",
                'oracle'  => 'SELECT COUNT(*) FROM USER_TABLES WHERE TABLE_NAME = UPPER(?)',
                'sqlsrv'  => 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?',
                'sqlite'  => "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name = ?",
                default   => null,
            };
            if ($sql === null) {
                return false;
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
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
     * Build placeholder string. Override to use function-based placeholders (e.g. Oracle TO_TIMESTAMP).
     */
    protected function buildPlaceholders(array $columns, array $schema): string
    {
        return implode(', ', array_fill(0, count($columns), '?'));
    }

    /**
     * Prepare row values for binding. Override for engine-specific value transformation.
     *
     * @param array<string, mixed> $row
     * @param string[]             $columns
     * @return list<mixed>
     */
    protected function prepareRowValues(array $row, array $columns, array $schema): array
    {
        $values = [];
        foreach ($columns as $colName) {
            $v        = $row[$colName] ?? null;
            $values[] = ($v === '') ? null : $v;
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
     * Run the full migration from $reader to this target engine.
     *
     * @param BaseMigrator         $reader Source reader
     * @param string               $dbName Target database/schema name
     * @param array<string, mixed> $params Connection parameters
     */
    public function migrate(BaseMigrator $reader, string $dbName, array $params): MigrationResult
    {
        $result = new MigrationResult($reader->getSourceEngine(), $this->getTargetEngine(), $dbName);

        try {
            $this->createDatabaseIfNotExists($dbName, $params);
            $conn = $this->createConnection($dbName, $params);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
                    $existingCount = (int)$conn->query("SELECT COUNT(*) FROM $tableEsc")->fetchColumn();
                    if ($existingCount > 0) {
                        $result->addSkipped($table, $existingCount);
                        continue;
                    }
                } else {
                    $createSQL = $this->generateCreateTableSQL($table, $schema);
                    try {
                        $conn->exec($createSQL);
                    } catch (PDOException $e) {
                        if (!$this->isTableAlreadyExistsError($e->getMessage())) {
                            $result->addError($table, 'CREATE TABLE: ' . $e->getMessage());
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
                $stmt      = $conn->prepare($insertSQL);

                $count = 0;
                foreach ($rows as $row) {
                    $values = $this->prepareRowValues($row, $columns, $schema);
                    $stmt->execute($values);
                    $count++;
                }

                $result->addMigrated($table, $count);
            } catch (Exception $e) {
                $result->addError($table, $e->getMessage());
            }
        }

        return $result;
    }
}
