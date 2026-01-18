<?php

namespace GenericDatabase\Helpers\Exporters;

use SQLite3;
use Exception;

/**
 * SQLite Exporter
 * Exports SQLite database tables to different formats
 */
class SQLiteExporter extends BaseExporter
{
    protected SQLite3 $dbh;

    /**
     * Constructor
     *
     * @param string $databasePath Path to SQLite database file
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(string $databasePath, string $outputPath)
    {
        // Check if SQLite3 extension is available
        if (!extension_loaded('sqlite3')) {
            throw new Exception(
                "SQLite3 extension is not available. " .
                "Please install and enable the sqlite3 PHP extension."
            );
        }

        if (!file_exists($databasePath)) {
            throw new Exception("Database file not found: {$databasePath}");
        }

        $this->dbh = new SQLite3($databasePath);
        $this->dbh->enableExceptions(true);

        parent::__construct($outputPath);
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $result = $this->dbh->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->tables[] = $row['name'];
        }
    }

    /**
     * Load schema information for all tables
     */
    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $result = $this->dbh->query("PRAGMA table_info({$table})");
            $columns = [];
            $primaryKey = null;

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = [
                    'name' => $row['name'],
                    'type' => $this->normalizeType($row['type']),
                    'notnull' => (bool)$row['notnull'],
                    'dflt_value' => $row['dflt_value'],
                    'pk' => (bool)$row['pk']
                ];

                if ($row['pk']) {
                    $primaryKey = $row['name'];
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
        $result = $this->dbh->query("SELECT * FROM {$table}");
        $data = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
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
            // First, try to get explicit foreign keys from SQLite
            $this->dbh->exec("PRAGMA foreign_keys = ON");
            $result = $this->dbh->query("PRAGMA foreign_key_list({$table})");

            if ($result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $foreignKeys[] = [
                        'from' => $row['from'],
                        'table' => $row['table'],
                        'to' => $row['to'] ?? 'id'
                    ];
                }
            }
        } catch (\Exception $e) {
            // If foreign keys are not enabled, continue with heuristics
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
