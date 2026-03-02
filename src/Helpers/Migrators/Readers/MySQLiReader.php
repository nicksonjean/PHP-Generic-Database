<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use mysqli;
use Exception;

/**
 * Source reader for MySQL / MariaDB databases using the MySQLi extension.
 */
class MySQLiReader extends BaseMigrator
{
    protected mysqli $dbh;
    protected string $database;

    /**
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        string $charset = 'utf8mb4'
    ) {
        if (!extension_loaded('mysqli')) {
            throw new Exception(
                'MySQLi extension is not available. '
                . 'Please install and enable the mysqli PHP extension.'
            );
        }

        $this->database = $database;
        $this->dbh      = new mysqli($host, $user, $password, $database, $port);

        if ($this->dbh->connect_error) {
            throw new Exception('MySQLi connection failed: ' . $this->dbh->connect_error);
        }

        $this->dbh->set_charset($charset);

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'mysql';
    }

    protected function loadTables(): void
    {
        $result = $this->dbh->query('SHOW TABLES');
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $this->tables[] = $row[0];
            }
            $result->free();
        }
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $result     = $this->dbh->query("SHOW FULL COLUMNS FROM `{$table}`");
            $columns    = [];
            $primaryKey = null;

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rawType   = $row['Type'];
                    $isPrimary = strtolower($row['Key']) === 'pri';

                    $columns[] = [
                        'name'       => $row['Field'],
                        'raw_type'   => strtoupper($rawType),
                        'canonical'  => $this->normalizeToCanonical($rawType),
                        'notnull'    => $row['Null'] === 'NO',
                        'dflt_value' => $row['Default'],
                        'pk'         => $isPrimary,
                    ];

                    if ($isPrimary && $primaryKey === null) {
                        $primaryKey = $row['Field'];
                    }
                }
                $result->free();
            }

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    public function getTableData(string $table): array
    {
        $result = $this->dbh->query("SELECT * FROM `{$table}`");
        $rows   = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
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
