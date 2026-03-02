<?php

namespace GenericDatabase\Helpers\Migrators\Writers\PDO;

use GenericDatabase\Helpers\Migrators\Writers\BasePDOWriter;
use PDO;

/**
 * Generic target writer using PDO.
 *
 * Useful when the desired driver is not covered by the dedicated writers, or
 * when the caller wants to provide a custom DSN pattern.
 *
 * Type conversion defaults to safe, widely-compatible SQL types.
 * For known engines (mysql, pgsql, sqlsrv) engine-specific types are used.
 *
 * @param string $driver      Short driver name: 'mysql', 'pgsql', 'sqlsrv', …
 * @param string $dsnTemplate DSN template with {dbname} placeholder.
 *                            Example: "mysql:host=localhost;port=3306;dbname={dbname};charset=utf8mb4"
 */
class PDOWriter extends BasePDOWriter
{
    private string $dsnTemplate;
    private string $driver;

    public function __construct(string $driver, string $dsnTemplate)
    {
        $this->driver      = strtolower($driver);
        $this->dsnTemplate = $dsnTemplate;
    }

    public function getTargetEngine(): string
    {
        return $this->driver;
    }

    public function escapeIdentifier(string $name): string
    {
        return match ($this->driver) {
            'mysql', 'mariadb'          => '`' . str_replace('`', '``', $name) . '`',
            'sqlsrv', 'mssql', 'dblib'  => '[' . str_replace(']', ']]', $name) . ']',
            default                     => '"' . str_replace('"', '""', $name) . '"',
        };
    }

    protected function convertType(string $canonical, string $rawType): string
    {
        return match ($this->driver) {
            'mysql', 'mariadb'          => $this->mysqlConvert($canonical, $rawType),
            'pgsql'                     => $this->pgsqlConvert($canonical, $rawType),
            'sqlsrv', 'mssql', 'dblib'  => $this->sqlsrvConvert($canonical, $rawType),
            default                     => $this->genericConvert($canonical, $rawType),
        };
    }

    private function mysqlConvert(string $c, string $r): string
    {
        return match ($c) {
            'bigint'   => 'BIGINT',
            'smallint' => 'SMALLINT',
            'integer' => 'INT',
            'boolean'  => 'TINYINT(1)',
            'float'    => 'FLOAT',
            'double' => 'DOUBLE',
            'decimal'  => 'DECIMAL(' . $this->extractSize($r, '10,2') . ')',
            'date'     => 'DATE',
            'time' => 'TIME',
            'datetime', 'timestamp' => 'DATETIME',
            'blob'     => 'BLOB',
            'clob', 'text' => 'LONGTEXT',
            'char'     => 'CHAR(' . $this->extractSize($r, '1') . ')',
            'varchar'  => 'VARCHAR(' . $this->extractSize($r, '255') . ')',
            default    => 'TEXT',
        };
    }

    private function pgsqlConvert(string $c, string $r): string
    {
        return match ($c) {
            'bigint'   => 'BIGINT',
            'smallint' => 'SMALLINT',
            'integer' => 'INTEGER',
            'boolean'  => 'BOOLEAN',
            'float'    => 'REAL',
            'double' => 'DOUBLE PRECISION',
            'decimal'  => 'NUMERIC(' . $this->extractSize($r, '10,2') . ')',
            'date'     => 'DATE',
            'time' => 'TIME',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'blob'     => 'BYTEA',
            'clob', 'text' => 'TEXT',
            'char'     => 'CHAR(' . $this->extractSize($r, '1') . ')',
            'varchar'  => 'VARCHAR(' . $this->extractSize($r, '255') . ')',
            default    => 'TEXT',
        };
    }

    private function sqlsrvConvert(string $c, string $r): string
    {
        return match ($c) {
            'bigint'   => 'BIGINT',
            'smallint' => 'SMALLINT',
            'integer' => 'INT',
            'boolean'  => 'BIT',
            'float', 'double' => 'FLOAT',
            'decimal'  => 'DECIMAL(' . $this->extractSize($r, '10,2') . ')',
            'date'     => 'DATE',
            'time' => 'TIME',
            'datetime', 'timestamp' => 'DATETIME2',
            'blob'     => 'VARBINARY(MAX)',
            'clob', 'text' => 'NVARCHAR(MAX)',
            'char'     => 'NCHAR(' . $this->extractSize($r, '1') . ')',
            'varchar'  => 'NVARCHAR(' . $this->extractSize($r, '255') . ')',
            default    => 'NVARCHAR(MAX)',
        };
    }

    private function genericConvert(string $c, string $r): string
    {
        return match ($c) {
            'bigint', 'integer', 'smallint', 'boolean' => 'INTEGER',
            'float', 'double', 'decimal'               => 'REAL',
            'date', 'time', 'datetime', 'timestamp'    => 'TEXT',
            'blob', 'clob', 'text'                     => 'TEXT',
            'char', 'varchar'                          => 'TEXT',
            default                                    => 'TEXT',
        };
    }

    protected function createDatabaseIfNotExists(string $dbName, array $params): void
    {
        // PDOWriter relies on the target database being pre-created,
        // or the DSN template pointing to a non-database-specific endpoint.
        // Engine-specific writers (MySQLWriter, PgSQLWriter, …) should be used
        // when automatic database creation is required.
    }

    protected function createConnection(string $dbName, array $params): PDO
    {
        $dsn = str_replace('{dbname}', $dbName, $this->dsnTemplate);
        return new PDO(
            $dsn,
            $params['user'] ?? '',
            $params['password'] ?? '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ] + ($params['options'] ?? [])
        );
    }
}
