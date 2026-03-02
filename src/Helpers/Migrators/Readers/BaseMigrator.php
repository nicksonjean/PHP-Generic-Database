<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

/**
 * Base abstract class for all source database readers used in migration.
 *
 * Reads schema and data from a source database and exposes them to writers.
 * Each engine-specific subclass implements table discovery, schema loading,
 * and data retrieval using the appropriate PHP extension.
 *
 * Schema columns include both the raw source type (for size preservation) and
 * a canonical type string (for cross-engine type mapping).
 */
abstract class BaseMigrator
{
    protected array $tables = [];
    protected array $tableSchemas = [];

    /**
     * Load all user table names from the source database.
     */
    abstract protected function loadTables(): void;

    /**
     * Load column schema for all tables.
     *
     * Each column entry must contain:
     *   - name        (string)  column name
     *   - raw_type    (string)  original type string from the source engine
     *   - canonical   (string)  normalised canonical type (see normalizeToCanonical)
     *   - notnull     (bool)    whether the column has a NOT NULL constraint
     *   - dflt_value  (?string) default value expression or null
     *   - pk          (bool)    whether the column is part of the primary key
     *
     * The table entry must also contain:
     *   - primaryKey  (?string) name of the single primary-key column, or null
     */
    abstract protected function loadTableSchemas(): void;

    /**
     * Retrieve all rows from the given table.
     *
     * @param string $table Table name
     * @return array<int, array<string, mixed>>
     */
    abstract public function getTableData(string $table): array;

    /**
     * Return a short lowercase identifier for the source engine.
     * Examples: 'sqlite', 'mysql', 'pgsql', 'oracle', 'firebird'.
     */
    abstract public function getSourceEngine(): string;

    /**
     * Map a raw source-engine type string to a canonical form used by writers
     * for cross-engine type conversion.
     *
     * Size specifiers such as "(255)" are stripped before matching so that
     * "VARCHAR(255)" and "VARCHAR(4000)" both normalise to 'varchar'.
     *
     * Canonical values:
     *   bigint | smallint | integer | boolean
     *   float  | double   | decimal
     *   text   | varchar  | char    | clob
     *   blob   | date     | time    | datetime | timestamp
     *
     * @param string $rawType Raw type string from the source engine
     * @return string Canonical type identifier (lowercase)
     */
    public function normalizeToCanonical(string $rawType): string
    {
        $type = strtoupper(trim($rawType));
        // Strip size specifiers: VARCHAR(255) -> VARCHAR
        $type = (string)preg_replace('/\s*\(.*\)/', '', $type);
        $type = trim($type);

        return match (true) {
            str_contains($type, 'BIGINT')                                        => 'bigint',
            str_contains($type, 'SMALLINT') || str_contains($type, 'TINYINT')   => 'smallint',
            str_contains($type, 'INTEGER') || str_contains($type, 'INT')         => 'integer',
            str_contains($type, 'BOOL')                                          => 'boolean',
            str_contains($type, 'TIMESTAMP')                                     => 'timestamp',
            str_contains($type, 'DATETIME')                                      => 'datetime',
            str_contains($type, 'DATE') && !str_contains($type, 'TIME')          => 'date',
            str_contains($type, 'TIME')                                          => 'time',
            str_contains($type, 'DOUBLE') || str_contains($type, 'PRECISION')    => 'double',
            str_contains($type, 'FLOAT') || str_contains($type, 'REAL')          => 'float',
            str_contains($type, 'DECIMAL') || str_contains($type, 'NUMERIC')
                || str_contains($type, 'MONEY') || str_contains($type, 'NUMBER') => 'decimal',
            str_contains($type, 'BLOB') && !str_contains($type, 'CLOB')          => 'blob',
            str_contains($type, 'CLOB')                                          => 'clob',
            str_contains($type, 'BYTEA') || str_contains($type, 'BINARY')        => 'blob',
            str_contains($type, 'VARCHAR') || str_contains($type, 'CHARACTER VARYING')
                || str_contains($type, 'VARCHAR2') || str_contains($type, 'NVARCHAR') => 'varchar',
            str_contains($type, 'CHAR')                                          => 'char',
            str_contains($type, 'TEXT')                                          => 'text',
            default                                                              => 'text',
        };
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTableSchemas(): array
    {
        return $this->tableSchemas;
    }

    /**
     * @return array{columns: array, primaryKey: ?string}
     */
    public function getTableSchema(string $table): array
    {
        return $this->tableSchemas[$table] ?? ['columns' => [], 'primaryKey' => null];
    }
}
