<?php

namespace GenericDatabase\Helpers\Exporters;

use mysqli;
use Exception;

/**
 * MySQLi Exporter
 * Exports MySQL database tables to different formats using MySQLi extension
 */
class MySQLiExporter extends BaseExporter
{
    protected mysqli $dbh;
    protected string $database;

    /**
     * Constructor
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database name
     * @param int $port Database port (default: 3306)
     * @param string $charset Database charset (default: utf8)
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 3306,
        string $charset = 'utf8'
    ) {
        if (!extension_loaded('mysqli')) {
            throw new Exception("MySQLi extension is not available. Please install and enable the mysqli PHP extension.");
        }

        $this->database = $database;
        $this->dbh = new mysqli($host, $user, $password, $database, $port);

        if ($this->dbh->connect_error) {
            throw new Exception("MySQLi connection failed: " . $this->dbh->connect_error);
        }

        $this->dbh->set_charset($charset);

        parent::__construct($outputPath);
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $result = $this->dbh->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $this->tables[] = $row[0];
            }
            $result->free();
        }
    }

    /**
     * Load schema information for all tables
     */
    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $result = $this->dbh->query("SHOW COLUMNS FROM `{$table}`");
            $columns = [];
            $primaryKey = null;

            if ($result) {
                while ($row = $result->fetch_assoc()) {
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
                $result->free();
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
        $result = $this->dbh->query("SELECT * FROM `{$table}`");
        $data = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->free();
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
                    COLUMN_NAME as 'from',
                    REFERENCED_TABLE_NAME as 'table',
                    REFERENCED_COLUMN_NAME as 'to'
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ";

            $stmt = $this->dbh->prepare($query);
            if ($stmt) {
                $stmt->bind_param('ss', $this->database, $table);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $foreignKeys[] = [
                        'from' => $row['from'],
                        'table' => $row['table'],
                        'to' => $row['to'] ?? 'id'
                    ];
                }

                $stmt->close();
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
        if (isset($this->dbh)) {
            $this->dbh->close();
        }
    }
}

