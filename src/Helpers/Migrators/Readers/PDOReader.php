<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use PDO;
use Exception;

/**
 * Generic source reader using PDO.
 *
 * Supports any PDO driver: mysql, pgsql, sqlite, oci, sqlsrv, firebird, etc.
 * Schema discovery uses driver-specific catalogue queries matched from the DSN.
 */
class PDOReader extends BaseMigrator
{
    protected PDO $pdo;
    protected string $driver;

    /**
     * @param string               $dsn      Full PDO DSN
     * @param string               $user     Username
     * @param string               $password Password
     * @param array<string, mixed> $options  PDO options
     * @throws Exception
     */
    public function __construct(
        string $dsn,
        string $user = '',
        string $password = '',
        array $options = []
    ) {
        $defaults = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $this->pdo    = new PDO($dsn, $user, $password, $defaults + $options);
        $this->driver = strtolower($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return match ($this->driver) {
            'mysql'                    => 'mysql',
            'pgsql'                    => 'pgsql',
            'sqlite', 'sqlite2'        => 'sqlite',
            'oci', 'oracle'            => 'oracle',
            'sqlsrv', 'mssql', 'dblib' => 'sqlsrv',
            'firebird', 'interbase'    => 'firebird',
            default                    => $this->driver,
        };
    }

    protected function loadTables(): void
    {
        $sql = match ($this->driver) {
            'mysql'                    => 'SHOW TABLES',
            'pgsql'                    => "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename",
            'sqlite', 'sqlite2'        => "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
            'oci', 'oracle'            => 'SELECT table_name FROM user_tables ORDER BY table_name',
            'sqlsrv', 'mssql', 'dblib' => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME",
            default                    => "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name",
        };

        $stmt = $this->pdo->query($sql);
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
                $name = $row[0];
                if ($this->driver === 'oci' || $this->driver === 'oracle') {
                    $name = strtolower($name);
                }
                $this->tables[] = $name;
            }
        }
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $columns    = [];
            $primaryKey = null;

            switch ($this->driver) {
                case 'sqlite':
                case 'sqlite2':
                    $safe = str_replace('"', '""', $table);
                    $stmt = $this->pdo->query("PRAGMA table_info(\"$safe\")");
                    foreach ($stmt->fetchAll() as $col) {
                        $raw  = $col['type'] ?: 'TEXT';
                        $isPk = (bool)$col['pk'];
                        $columns[] = $this->buildColumn($col['name'], $raw, (bool)$col['notnull'], $col['dflt_value'], $isPk);
                        if ($isPk && $primaryKey === null) {
                            $primaryKey = $col['name'];
                        }
                    }
                    break;

                case 'mysql':
                    $stmt = $this->pdo->query("SHOW FULL COLUMNS FROM `$table`");
                    foreach ($stmt->fetchAll() as $col) {
                        $isPk = strtolower($col['Key']) === 'pri';
                        $columns[] = $this->buildColumn($col['Field'], $col['Type'], $col['Null'] === 'NO', $col['Default'], $isPk);
                        if ($isPk && $primaryKey === null) {
                            $primaryKey = $col['Field'];
                        }
                    }
                    break;

                case 'pgsql':
                    $stmt = $this->pdo->prepare(
                        "SELECT column_name, data_type, character_maximum_length, is_nullable, column_default
                         FROM information_schema.columns
                         WHERE table_schema = 'public' AND table_name = ?
                         ORDER BY ordinal_position"
                    );
                    $stmt->execute([$table]);
                    $pkStmt = $this->pdo->prepare(
                        "SELECT a.attname FROM pg_index i
                         JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                         WHERE i.indrelid = ?::regclass AND i.indisprimary"
                    );
                    $pkStmt->execute(["\"$table\""]);
                    $pkCols = $pkStmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($stmt->fetchAll() as $col) {
                        $raw  = $col['data_type'];
                        $size = $col['character_maximum_length'];
                        $displayType = strtoupper($raw) . ($size ? "($size)" : '');
                        $isPk = in_array($col['column_name'], $pkCols, true);
                        $columns[] = $this->buildColumn($col['column_name'], $displayType, $col['is_nullable'] === 'NO', $col['column_default'], $isPk);
                        if ($isPk && $primaryKey === null) {
                            $primaryKey = $col['column_name'];
                        }
                    }
                    break;

                case 'oci':
                case 'oracle':
                    $uTable = strtoupper($table);
                    $stmt   = $this->pdo->prepare(
                        "SELECT column_name, data_type, data_length, nullable, data_default
                         FROM user_tab_columns WHERE table_name = ? ORDER BY column_id"
                    );
                    $stmt->execute([$uTable]);
                    $pkStmt = $this->pdo->prepare(
                        "SELECT ucc.column_name FROM user_constraints uc
                         JOIN user_cons_columns ucc ON uc.constraint_name = ucc.constraint_name
                         WHERE uc.constraint_type = 'P' AND uc.table_name = ?"
                    );
                    $pkStmt->execute([$uTable]);
                    $pkCols = array_map('strtolower', $pkStmt->fetchAll(PDO::FETCH_COLUMN));
                    foreach ($stmt->fetchAll() as $col) {
                        $colName = strtolower($col['column_name'] ?? $col['COLUMN_NAME']);
                        $rawType = $col['data_type'] ?? $col['DATA_TYPE'];
                        $isPk    = in_array($colName, $pkCols, true);
                        $columns[] = $this->buildColumn($colName, strtoupper($rawType), ($col['nullable'] ?? $col['NULLABLE']) === 'N', null, $isPk);
                        if ($isPk && $primaryKey === null) {
                            $primaryKey = $colName;
                        }
                    }
                    break;

                case 'sqlsrv':
                case 'mssql':
                case 'dblib':
                    $stmt = $this->pdo->prepare(
                        "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT
                         FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION"
                    );
                    $stmt->execute([$table]);
                    $pkStmt = $this->pdo->prepare(
                        "SELECT kcu.COLUMN_NAME
                         FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                         JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                           ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND tc.TABLE_NAME = kcu.TABLE_NAME
                         WHERE tc.CONSTRAINT_TYPE = 'PRIMARY KEY' AND tc.TABLE_NAME = ?"
                    );
                    $pkStmt->execute([$table]);
                    $pkCols = $pkStmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($stmt->fetchAll() as $col) {
                        $raw  = $col['DATA_TYPE'];
                        $size = $col['CHARACTER_MAXIMUM_LENGTH'];
                        $displayType = strtoupper($raw) . ($size && $size != -1 ? "($size)" : ($size == -1 ? '(MAX)' : ''));
                        $isPk = in_array($col['COLUMN_NAME'], $pkCols, true);
                        $columns[] = $this->buildColumn($col['COLUMN_NAME'], $displayType, $col['IS_NULLABLE'] === 'NO', $col['COLUMN_DEFAULT'], $isPk);
                        if ($isPk && $primaryKey === null) {
                            $primaryKey = $col['COLUMN_NAME'];
                        }
                    }
                    break;

                default:
                    $stmt = $this->pdo->query("SELECT * FROM \"$table\" LIMIT 0");
                    for ($i = 0; $i < $stmt->columnCount(); $i++) {
                        $meta = $stmt->getColumnMeta($i);
                        $raw  = $meta['native_type'] ?? 'text';
                        $columns[] = $this->buildColumn($meta['name'], $raw, false, null, false);
                    }
            }

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    private function buildColumn(string $name, string $rawType, bool $notnull, mixed $dfltValue, bool $pk): array
    {
        return [
            'name'       => $name,
            'raw_type'   => strtoupper($rawType),
            'canonical'  => $this->normalizeToCanonical($rawType),
            'notnull'    => $notnull,
            'dflt_value' => $dfltValue !== null ? (string)$dfltValue : null,
            'pk'         => $pk,
        ];
    }

    public function getTableData(string $table): array
    {
        $esc = match ($this->driver) {
            'mysql'                    => "`$table`",
            'sqlsrv', 'mssql', 'dblib' => "[$table]",
            default                    => "\"$table\"",
        };
        $stmt = $this->pdo->query("SELECT * FROM $esc");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
