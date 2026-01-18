<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * OCI Exporter
 * Exports Oracle database tables to different formats using OCI8 extension
 */
class OCIExporter extends BaseExporter
{
    protected $dbh;
    protected string $database;

    /**
     * Constructor
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database name (SID or Service Name)
     * @param int $port Database port (default: 1521)
     * @param string $charset Database charset (default: AL32UTF8)
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 1521,
        string $charset = 'AL32UTF8'
    ) {
        if (!extension_loaded('oci8')) {
            throw new Exception("OCI8 extension is not available. Please install and enable the oci8 PHP extension.");
        }

        $this->database = $database;
        $dsn = "{$host}:{$port}/{$database}";
        $this->dbh = oci_connect($user, $password, $dsn, $charset);

        if (!$this->dbh) {
            $error = oci_error();
            throw new Exception("OCI connection failed: " . ($error['message'] ?? 'Unknown error'));
        }

        parent::__construct($outputPath);
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $query = "SELECT table_name FROM user_tables ORDER BY table_name";
        $stmt = oci_parse($this->dbh, $query);
        if ($stmt && oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $this->tables[] = strtoupper($row['TABLE_NAME']);
            }
            oci_free_statement($stmt);
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
                    nullable,
                    data_default
                FROM user_tab_columns
                WHERE table_name = :table
                ORDER BY column_id
            ";

            $stmt = oci_parse($this->dbh, $query);
            $tableUpper = strtoupper($table);
            oci_bind_by_name($stmt, ':table', $tableUpper);
            $columns = [];
            $primaryKey = null;

            if ($stmt && oci_execute($stmt)) {
                while ($row = oci_fetch_assoc($stmt)) {
                    $columns[] = [
                        'name' => strtolower($row['COLUMN_NAME']),
                        'type' => $this->normalizeType($row['DATA_TYPE']),
                        'notnull' => $row['NULLABLE'] === 'N',
                        'dflt_value' => $row['DATA_DEFAULT'],
                        'pk' => false
                    ];
                }
                oci_free_statement($stmt);
            }

            // Get primary key separately
            $pkQuery = "
                SELECT column_name
                FROM user_cons_columns
                WHERE constraint_name = (
                    SELECT constraint_name
                    FROM user_constraints
                    WHERE table_name = :table AND constraint_type = 'P'
                )
            ";
            $pkStmt = oci_parse($this->dbh, $pkQuery);
            oci_bind_by_name($pkStmt, ':table', $tableUpper);
            if ($pkStmt && oci_execute($pkStmt)) {
                if ($pkRow = oci_fetch_assoc($pkStmt)) {
                    $primaryKey = strtolower($pkRow['COLUMN_NAME']);

                    // Update primary key flag in columns
                    foreach ($columns as &$column) {
                        if ($column['name'] === $primaryKey) {
                            $column['pk'] = true;
                        }
                    }
                }
                oci_free_statement($pkStmt);
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
        $query = "SELECT * FROM \"{$table}\"";
        $stmt = oci_parse($this->dbh, $query);
        $data = [];

        if ($stmt && oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                // Convert keys to lowercase for consistency
                $normalizedRow = [];
                foreach ($row as $key => $value) {
                    $normalizedRow[strtolower($key)] = $value;
                }
                $data[] = $normalizedRow;
            }
            oci_free_statement($stmt);
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
            $tableUpper = strtoupper($table);
            $query = "
                SELECT
                    a.column_name as \"from\",
                    c_pk.table_name as \"table\",
                    b.column_name as \"to\"
                FROM user_cons_columns a
                JOIN user_constraints c ON a.constraint_name = c.constraint_name
                JOIN user_constraints c_pk ON c.r_constraint_name = c_pk.constraint_name
                JOIN user_cons_columns b ON c_pk.constraint_name = b.constraint_name AND b.position = a.position
                WHERE c.constraint_type = 'R'
                  AND a.table_name = :table
            ";

            $stmt = oci_parse($this->dbh, $query);
            oci_bind_by_name($stmt, ':table', $tableUpper);
            if ($stmt && oci_execute($stmt)) {
                while ($row = oci_fetch_assoc($stmt)) {
                    $foreignKeys[] = [
                        'from' => strtolower($row['from']),
                        'table' => strtolower($row['table']),
                        'to' => strtolower($row['to'] ?? 'id')
                    ];
                }
                oci_free_statement($stmt);
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
        if (is_resource($this->dbh)) {
            oci_close($this->dbh);
        }
    }
}
