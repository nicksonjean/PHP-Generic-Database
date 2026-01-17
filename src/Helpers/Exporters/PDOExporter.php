<?php

namespace GenericDatabase\Helpers\Exporters;

use PDO;
use Exception;

/**
 * PDO Exporter
 * Exports database tables to different formats using PDO (supports multiple drivers)
 */
class PDOExporter extends BaseExporter
{
    protected PDO $dbh;
    protected string $driver;
    protected string $database;

    /**
     * Constructor
     *
     * @param string $driver PDO driver (mysql, pgsql, sqlite, oci, sqlsrv, etc.)
     * @param string $dsn Data Source Name
     * @param string $user Database user
     * @param string $password Database password
     * @param string $outputPath Path where exported files will be saved
     * @param array $options PDO options
     * @throws Exception
     */
    public function __construct(
        string $driver,
        string $dsn,
        string $user,
        string $password,
        string $outputPath,
        array $options = []
    ) {
        if (!extension_loaded('pdo')) {
            throw new Exception("PDO extension is not available. Please install and enable the pdo PHP extension.");
        }

        $this->driver = strtolower($driver);
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $options = array_merge($defaultOptions, $options);

        try {
            $this->dbh = new PDO($dsn, $user, $password, $options);
            
            // Extract database name from DSN for some queries
            $this->database = $this->extractDatabaseName($dsn);
        } catch (\PDOException $e) {
            throw new Exception("PDO connection failed: " . $e->getMessage());
        }

        parent::__construct($outputPath);
    }

    /**
     * Extract database name from DSN
     *
     * @param string $dsn Data Source Name
     * @return string Database name
     */
    protected function extractDatabaseName(string $dsn): string
    {
        if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
            return $matches[1];
        }
        if (preg_match('/database=([^;]+)/', $dsn, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $query = match ($this->driver) {
            'mysql' => "SHOW TABLES",
            'pgsql' => "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename",
            'sqlite', 'sqlite2' => "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
            'oci', 'oracle' => "SELECT table_name FROM user_tables ORDER BY table_name",
            'sqlsrv', 'mssql', 'dblib' => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME",
            default => "SHOW TABLES" // Default to MySQL syntax
        };

        try {
            $stmt = $this->dbh->query($query);
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $tableName = $row[0];
                    // Normalize table name (Oracle returns uppercase)
                    if ($this->driver === 'oci' || $this->driver === 'oracle') {
                        $tableName = strtolower($tableName);
                    }
                    $this->tables[] = $tableName;
                }
            }
        } catch (\PDOException $e) {
            throw new Exception("Failed to load tables: " . $e->getMessage());
        }
    }

    /**
     * Load schema information for all tables
     */
    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $columns = [];
            $primaryKey = null;

            switch ($this->driver) {
                case 'mysql':
                    $query = "SHOW COLUMNS FROM `{$table}`";
                    $stmt = $this->dbh->query($query);
                    if ($stmt) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $isPrimaryKey = strtolower($row['Key']) === 'pri';
                            $columns[] = [
                                'name' => $row['Field'],
                                'type' => $this->normalizeType($row['Type']),
                                'notnull' => $row['Null'] === 'NO',
                                'dflt_value' => $row['Default'],
                                'pk' => $isPrimaryKey
                            ];
                            if ($isPrimaryKey) {
                                $primaryKey = $row['Field'];
                            }
                        }
                    }
                    break;

                case 'pgsql':
                    $query = "
                        SELECT 
                            column_name,
                            data_type,
                            is_nullable,
                            column_default
                        FROM information_schema.columns
                        WHERE table_name = ? AND table_schema = 'public'
                        ORDER BY ordinal_position
                    ";
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute([$table]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = [
                            'name' => $row['column_name'],
                            'type' => $this->normalizeType($row['data_type']),
                            'notnull' => $row['is_nullable'] === 'NO',
                            'dflt_value' => $row['column_default'],
                            'pk' => false
                        ];
                    }
                    // Get primary key separately
                    $pkQuery = "
                        SELECT a.attname
                        FROM pg_index i
                        JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                        WHERE i.indrelid = ?::regclass AND i.indisprimary
                    ";
                    $pkStmt = $this->dbh->prepare($pkQuery);
                    $pkStmt->execute([$table]);
                    if ($pkRow = $pkStmt->fetch(PDO::FETCH_ASSOC)) {
                        $primaryKey = $pkRow['attname'];
                    }
                    break;

                case 'sqlite':
                case 'sqlite2':
                    $query = "PRAGMA table_info(`{$table}`)";
                    $stmt = $this->dbh->query($query);
                    if ($stmt) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $isPrimaryKey = (bool)$row['pk'];
                            $columns[] = [
                                'name' => $row['name'],
                                'type' => $this->normalizeType($row['type']),
                                'notnull' => (bool)$row['notnull'],
                                'dflt_value' => $row['dflt_value'],
                                'pk' => $isPrimaryKey
                            ];
                            if ($isPrimaryKey) {
                                $primaryKey = $row['name'];
                            }
                        }
                    }
                    break;

                case 'oci':
                case 'oracle':
                    $query = "
                        SELECT 
                            column_name,
                            data_type,
                            nullable,
                            data_default
                        FROM user_tab_columns
                        WHERE table_name = UPPER(?)
                        ORDER BY column_id
                    ";
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute([$table]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = [
                            'name' => strtolower($row['COLUMN_NAME']),
                            'type' => $this->normalizeType($row['DATA_TYPE']),
                            'notnull' => $row['NULLABLE'] === 'N',
                            'dflt_value' => $row['DATA_DEFAULT'],
                            'pk' => false
                        ];
                    }
                    // Get primary key separately
                    $pkQuery = "
                        SELECT column_name
                        FROM user_cons_columns
                        WHERE constraint_name = (
                            SELECT constraint_name
                            FROM user_constraints
                            WHERE table_name = UPPER(?) AND constraint_type = 'P'
                        )
                    ";
                    $pkStmt = $this->dbh->prepare($pkQuery);
                    $pkStmt->execute([$table]);
                    if ($pkRow = $pkStmt->fetch(PDO::FETCH_ASSOC)) {
                        $primaryKey = strtolower($pkRow['COLUMN_NAME']);
                    }
                    break;

                case 'sqlsrv':
                case 'mssql':
                case 'dblib':
                    $query = "
                        SELECT 
                            COLUMN_NAME,
                            DATA_TYPE,
                            IS_NULLABLE,
                            COLUMN_DEFAULT
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_NAME = ?
                        ORDER BY ORDINAL_POSITION
                    ";
                    $stmt = $this->dbh->prepare($query);
                    $stmt->execute([$table]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = [
                            'name' => $row['COLUMN_NAME'],
                            'type' => $this->normalizeType($row['DATA_TYPE']),
                            'notnull' => $row['IS_NULLABLE'] === 'NO',
                            'dflt_value' => $row['COLUMN_DEFAULT'],
                            'pk' => false
                        ];
                    }
                    // Get primary key separately
                    $pkQuery = "
                        SELECT COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE TABLE_NAME = ? AND CONSTRAINT_NAME IN (
                            SELECT CONSTRAINT_NAME
                            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                            WHERE TABLE_NAME = ? AND CONSTRAINT_TYPE = 'PRIMARY KEY'
                        )
                    ";
                    $pkStmt = $this->dbh->prepare($pkQuery);
                    $pkStmt->execute([$table, $table]);
                    if ($pkRow = $pkStmt->fetch(PDO::FETCH_ASSOC)) {
                        $primaryKey = $pkRow['COLUMN_NAME'];
                    }
                    break;

                default:
                    // Generic fallback - try MySQL syntax
                    $query = "SHOW COLUMNS FROM `{$table}`";
                    $stmt = $this->dbh->query($query);
                    if ($stmt) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $isPrimaryKey = isset($row['Key']) && strtolower($row['Key']) === 'pri';
                            $columns[] = [
                                'name' => $row['Field'] ?? $row['column_name'] ?? '',
                                'type' => $this->normalizeType($row['Type'] ?? $row['data_type'] ?? ''),
                                'notnull' => ($row['Null'] ?? $row['is_nullable'] ?? 'YES') === 'NO',
                                'dflt_value' => $row['Default'] ?? $row['column_default'] ?? null,
                                'pk' => $isPrimaryKey
                            ];
                            if ($isPrimaryKey) {
                                $primaryKey = $row['Field'] ?? $row['column_name'] ?? null;
                            }
                        }
                    }
            }

            $this->tableSchemas[$table] = [
                'columns' => $columns,
                'primaryKey' => $primaryKey
            ];
        }
    }

    /**
     * Get all data from a table
     *
     * @param string $table Table name
     * @return array Array of rows
     */
    public function getTableData(string $table): array
    {
        // Use appropriate quoting based on driver
        $quotedTable = match ($this->driver) {
            'mysql' => "`{$table}`",
            'pgsql' => "\"{$table}\"",
            'sqlite', 'sqlite2' => "`{$table}`",
            'oci', 'oracle' => "\"{$table}\"",
            'sqlsrv', 'mssql', 'dblib' => "[{$table}]",
            default => "`{$table}`"
        };

        $query = "SELECT * FROM {$quotedTable}";
        $stmt = $this->dbh->query($query);
        $data = [];

        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Get foreign keys for a table
     *
     * @param string $table Table name
     * @return array Array of foreign keys
     */
    public function getForeignKeys(string $table): array
    {
        $foreignKeys = [];

        try {
            $query = match ($this->driver) {
                'mysql' => "
                    SELECT 
                        COLUMN_NAME as 'from',
                        REFERENCED_TABLE_NAME as 'table',
                        REFERENCED_COLUMN_NAME as 'to'
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ?
                      AND TABLE_NAME = ?
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ",
                'pgsql' => "
                    SELECT
                        kcu.column_name as 'from',
                        ccu.table_name as 'table',
                        ccu.column_name as 'to'
                    FROM information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                        ON ccu.constraint_name = tc.constraint_name
                        AND ccu.table_schema = tc.table_schema
                    WHERE tc.constraint_type = 'FOREIGN KEY'
                      AND tc.table_name = ?
                      AND tc.table_schema = 'public'
                ",
                'oci', 'oracle' => "
                    SELECT
                        a.column_name as 'from',
                        c_pk.table_name as 'table',
                        b.column_name as 'to'
                    FROM user_cons_columns a
                    JOIN user_constraints c ON a.constraint_name = c.constraint_name
                    JOIN user_constraints c_pk ON c.r_constraint_name = c_pk.constraint_name
                    JOIN user_cons_columns b ON c_pk.constraint_name = b.constraint_name AND b.position = a.position
                    WHERE c.constraint_type = 'R'
                      AND a.table_name = UPPER(?)
                ",
                'sqlsrv', 'mssql', 'dblib' => "
                    SELECT
                        COLUMN_NAME as 'from',
                        REFERENCED_TABLE_NAME as 'table',
                        REFERENCED_COLUMN_NAME as 'to'
                    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
                    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                        ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    WHERE kcu.TABLE_NAME = ?
                ",
                default => null
            };

            if ($query) {
                $stmt = $this->dbh->prepare($query);
                if ($this->driver === 'mysql') {
                    $stmt->execute([$this->database, $table]);
                } else {
                    $stmt->execute([$table]);
                }

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fromData = $row['from'] ?? $row['FROM'] ?? '';
                    $refTable = $row['table'] ?? $row['TABLE'] ?? '';
                    $toData = $row['to'] ?? $row['TO'] ?? 'id';

                    // Normalize for Oracle
                    if ($this->driver === 'oci' || $this->driver === 'oracle') {
                        $fromData = strtolower($fromData);
                        $refTable = strtolower($refTable);
                        $toData = strtolower($toData);
                    }

                    $foreignKeys[] = [
                        'from' => $fromData,
                        'table' => $refTable,
                        'to' => $toData
                    ];
                }
            }
        } catch (\Exception $e) {
            // If query fails, continue with heuristics
        }

        // If no explicit foreign keys found, use heuristics based on naming conventions
        if (empty($foreignKeys)) {
            $foreignKeys = parent::getForeignKeys($table);
        }

        return $foreignKeys;
    }

    /**
     * Destructor - close database connection
     */
    public function __destruct()
    {
        // PDO connections are automatically closed when the object is destroyed
        // No explicit cleanup needed
    }
}
