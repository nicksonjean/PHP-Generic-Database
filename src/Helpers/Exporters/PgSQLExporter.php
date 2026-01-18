<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * PgSQL Exporter
 * Exports PostgreSQL database tables to different formats using pgsql extension
 */
class PgSQLExporter extends BaseExporter
{
    /** @var \PgSql\Connection|resource|false */
    protected $dbh;
    protected string $database;

    /**
     * Constructor
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database name
     * @param int $port Database port (default: 5432)
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 5432
    ) {
        if (!extension_loaded('pgsql')) {
            throw new Exception("PgSQL extension is not available. Please install and enable the pgsql PHP extension.");
        }

        $this->database = $database;
        $dsn = "host={$host} port={$port} dbname={$database} user={$user} password={$password}";
        $this->dbh = pg_connect($dsn);

        if (!$this->dbh) {
            throw new Exception("PgSQL connection failed: " . pg_last_error());
        }

        parent::__construct($outputPath);
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $result = pg_query($this->dbh, "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $this->tables[] = $row['tablename'];
            }
            pg_free_result($result);
        }
    }

    /**
     * Load schema information for all tables
     */
    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            // Get columns
            $query = "
                SELECT 
                    column_name,
                    data_type,
                    is_nullable,
                    column_default
                FROM information_schema.columns
                WHERE table_name = $1 
                  AND table_schema = 'public'
                ORDER BY ordinal_position
            ";

            $result = pg_query_params($this->dbh, $query, [$table]);
            $columns = [];
            $primaryKey = null;

            if ($result) {
                while ($row = pg_fetch_assoc($result)) {
                    $columns[] = [
                        'name' => $row['column_name'],
                        'type' => $this->normalizeType($row['data_type']),
                        'notnull' => $row['is_nullable'] === 'NO',
                        'dflt_value' => $row['column_default'],
                        'pk' => false
                    ];
                }
                pg_free_result($result);
            }

            // Get primary key separately
            $pkQuery = "
                SELECT a.attname
                FROM pg_index i
                JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                WHERE i.indrelid = $1::regclass AND i.indisprimary
                LIMIT 1
            ";
            $pkResult = pg_query_params($this->dbh, $pkQuery, [$table]);
            if ($pkResult && pg_num_rows($pkResult) > 0) {
                $pkRow = pg_fetch_assoc($pkResult);
                $primaryKey = $pkRow['attname'];
                pg_free_result($pkResult);
                
                // Update primary key flag in columns
                foreach ($columns as &$column) {
                    if ($column['name'] === $primaryKey) {
                        $column['pk'] = true;
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
        $result = pg_query($this->dbh, "SELECT * FROM \"{$table}\"");
        $data = [];

        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $data[] = $row;
            }
            pg_free_result($result);
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
            $query = "
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
            ";

            $result = pg_query_params($this->dbh, $query, [$table]);
            if ($result) {
                while ($row = pg_fetch_assoc($result)) {
                    $foreignKeys[] = [
                        'from' => $row['from'],
                        'table' => $row['table'],
                        'to' => $row['to'] ?? 'id'
                    ];
                }
                pg_free_result($result);
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
        if ($this->dbh !== false && $this->dbh !== null) {
            pg_close($this->dbh);
        }
    }
}

