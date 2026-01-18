<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * ODBC Exporter
 * Exports database tables to different formats using ODBC (supports multiple drivers)
 */
class ODBCExporter extends BaseExporter
{
    protected $dbh;
    protected string $driver;
    protected string $database;

    /**
     * Constructor
     *
     * @param string $driver ODBC driver name
     * @param string $dsn Data Source Name or connection string
     * @param string $user Database user
     * @param string $password Database password
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(
        string $driver,
        string $dsn,
        string $user,
        string $password,
        string $outputPath
    ) {
        if (!extension_loaded('odbc')) {
            throw new Exception("ODBC extension is not available. Please install and enable the odbc PHP extension.");
        }

        $this->driver = strtolower($driver);
        $this->dbh = odbc_connect($dsn, $user, $password);

        if (!$this->dbh) {
            throw new Exception("ODBC connection failed: " . odbc_errormsg());
        }

        // Extract database name from DSN if possible
        $this->database = $this->extractDatabaseName($dsn);

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
        if (preg_match('/Database=([^;]+)/i', $dsn, $matches)) {
            return $matches[1];
        }
        if (preg_match('/DBQ=([^;]+)/i', $dsn, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $result = odbc_tables($this->dbh);
        if ($result !== false) {
            while (odbc_fetch_row($result)) {
                $tableType = odbc_result($result, "TABLE_TYPE");
                $tableName = odbc_result($result, "TABLE_NAME");
                
                // Skip system tables
                if ($tableType !== "SYSTEM TABLE" && 
                    $tableType !== "SYSTEM VIEW" &&
                    !str_starts_with($tableName, "MSys") &&
                    !str_starts_with($tableName, "sqlite_")) {
                    $this->tables[] = $tableName;
                }
            }
        }
    }

    /**
     * Load schema information for all tables
     */
    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $result = odbc_columns($this->dbh, null, null, $table);
            $columns = [];
            $primaryKey = null;

            if ($result !== false) {
                while (odbc_fetch_row($result)) {
                    $columnName = odbc_result($result, "COLUMN_NAME");
                    $dataType = odbc_result($result, "TYPE_NAME");
                    $nullable = odbc_result($result, "NULLABLE");
                    $columnDef = odbc_result($result, "COLUMN_DEF");

                    $columns[] = [
                        'name' => $columnName,
                        'type' => $this->normalizeType($dataType),
                        'notnull' => $nullable == 0,
                        'dflt_value' => $columnDef,
                        'pk' => false // Will be determined separately
                    ];
                }
            }

            // Try to get primary key
            try {
                $pkResult = @odbc_primarykeys($this->dbh, '', '', $table);
                if ($pkResult !== false) {
                    if (odbc_fetch_row($pkResult)) {
                        $primaryKey = odbc_result($pkResult, "COLUMN_NAME");
                    }
                }
            } catch (\Exception $e) {
                // Primary key query may not be supported by all ODBC drivers
            }

            // Update primary key flag in columns
            foreach ($columns as &$column) {
                if ($column['name'] === $primaryKey) {
                    $column['pk'] = true;
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
        $query = "SELECT * FROM [{$table}]";
        $result = odbc_exec($this->dbh, $query);
        $data = [];

        if ($result) {
            $numFields = odbc_num_fields($result);
            while (odbc_fetch_row($result)) {
                $row = [];
                for ($i = 1; $i <= $numFields; $i++) {
                    $fieldName = odbc_field_name($result, $i);
                    $row[$fieldName] = odbc_result($result, $i);
                }
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
            $result = @odbc_foreignkeys(
                $this->dbh,
                '', // pk_catalog
                '', // pk_schema
                '', // pk_table
                '', // fk_catalog
                '', // fk_schema
                $table  // fk_table
            );

            if ($result !== false) {
                while (odbc_fetch_row($result)) {
                    $fkColumn = odbc_result($result, "FK_COLUMN_NAME");
                    $pkTable = odbc_result($result, "PK_TABLE_NAME");
                    $pkColumn = odbc_result($result, "PK_COLUMN_NAME");
                    
                    if ($fkColumn && $pkTable) {
                        $foreignKeys[] = [
                            'from' => $fkColumn,
                            'table' => $pkTable,
                            'to' => $pkColumn ?: 'id'
                        ];
                    }
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
        if (is_resource($this->dbh)) {
            odbc_close($this->dbh);
        }
    }
}

