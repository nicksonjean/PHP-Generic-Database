<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use SQLite3;
use Exception;

/**
 * Source reader for SQLite databases.
 *
 * Uses the SQLite3 extension to read table structure and data.
 * Column types are stored as-is (e.g. "INTEGER", "VARCHAR(255)") so that
 * target writers can preserve size information when converting types.
 */
class SQLiteReader extends BaseMigrator
{
    protected SQLite3 $dbh;

    /**
     * @param string $databasePath Absolute path to the SQLite database file
     * @throws Exception
     */
    public function __construct(string $databasePath)
    {
        if (!extension_loaded('sqlite3')) {
            throw new Exception(
                'SQLite3 extension is not available. '
                . 'Please install and enable the sqlite3 PHP extension.'
            );
        }

        if (!file_exists($databasePath)) {
            throw new Exception("SQLite database file not found: {$databasePath}");
        }

        $this->dbh = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $this->dbh->enableExceptions(true);

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'sqlite';
    }

    protected function loadTables(): void
    {
        $result = $this->dbh->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->tables[] = $row['name'];
        }
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $safe   = str_replace('"', '""', $table);
            $result = $this->dbh->query("PRAGMA table_info(\"$safe\")");

            $columns    = [];
            $primaryKey = null;

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rawType = (string)($row['type'] ?? '');
                if (trim($rawType) === '') {
                    $rawType = 'TEXT';
                }

                $columns[] = [
                    'name'       => $row['name'],
                    'raw_type'   => strtoupper($rawType),
                    'canonical'  => $this->normalizeToCanonical($rawType),
                    'notnull'    => (bool)$row['notnull'],
                    'dflt_value' => $row['dflt_value'],
                    'pk'         => (bool)$row['pk'],
                ];

                if ($row['pk']) {
                    $primaryKey = $row['name'];
                }
            }

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    public function getTableData(string $table): array
    {
        $safe   = str_replace('"', '""', $table);
        $result = $this->dbh->query("SELECT * FROM \"$safe\"");
        $rows   = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function __destruct()
    {
        if (isset($this->dbh)) {
            $this->dbh->close();
        }
    }
}
