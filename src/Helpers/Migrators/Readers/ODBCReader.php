<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use Exception;

/**
 * Source reader for ODBC data sources using the odbc extension.
 *
 * Works with any ODBC driver (Access, dBase, Excel, SQL Server, etc.).
 * Uses ODBC catalogue functions to discover tables and column metadata.
 */
class ODBCReader extends BaseMigrator
{
    /** @var resource */
    protected $dbh;
    protected string $database;

    /**
     * @param string $dsn      Full ODBC connection string or DSN name
     * @param string $user     Username
     * @param string $password Password
     * @throws Exception
     */
    public function __construct(string $dsn, string $user = '', string $password = '')
    {
        if (!extension_loaded('odbc')) {
            throw new Exception(
                'ODBC extension is not available. '
                . 'Please install and enable the odbc PHP extension.'
            );
        }

        $conn = odbc_connect($dsn, $user, $password);
        if (!$conn) {
            throw new Exception('ODBC connection failed for DSN: ' . $dsn);
        }

        $this->dbh = $conn;

        if (preg_match('/(?:Database|DBQ|DSN)\s*=\s*([^;]+)/i', $dsn, $m)) {
            $this->database = trim(basename($m[1], '.mdb'));
        } else {
            $this->database = 'odbc';
        }

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'odbc';
    }

    protected function loadTables(): void
    {
        $result = odbc_tables($this->dbh, '', '', '', 'TABLE');
        if ($result) {
            while ($row = odbc_fetch_array($result)) {
                $name = $row['TABLE_NAME'];
                if (!str_starts_with((string)$name, 'MSys') && !str_starts_with((string)$name, 'sys')) {
                    $this->tables[] = $name;
                }
            }
        }
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            // Pass empty strings instead of null — odbc_* functions require string params in PHP 8
            $colResult = odbc_columns($this->dbh, '', '', $table, '');
            $pkResult  = odbc_primarykeys($this->dbh, '', '', $table);

            $pkColumns = [];
            if ($pkResult) {
                while ($row = odbc_fetch_array($pkResult)) {
                    $pkColumns[] = $row['COLUMN_NAME'];
                }
            }

            $columns    = [];
            $primaryKey = null;

            if ($colResult) {
                while ($row = odbc_fetch_array($colResult)) {
                    $rawType = $row['TYPE_NAME'];
                    $size    = (int)($row['COLUMN_SIZE'] ?? 0);

                    $displayType = strtoupper($rawType);
                    if ($size > 0 && in_array(strtoupper($rawType), ['VARCHAR', 'CHAR', 'NVARCHAR', 'NCHAR'], true)) {
                        $displayType .= "($size)";
                    }

                    $isPk = in_array($row['COLUMN_NAME'], $pkColumns, true);

                    $columns[] = [
                        'name'       => $row['COLUMN_NAME'],
                        'raw_type'   => $displayType,
                        'canonical'  => $this->normalizeToCanonical($rawType),
                        'notnull'    => $row['NULLABLE'] == 0,
                        'dflt_value' => $row['COLUMN_DEF'] ?? null,
                        'pk'         => $isPk,
                    ];

                    if ($isPk && $primaryKey === null) {
                        $primaryKey = $row['COLUMN_NAME'];
                    }
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
        $result = odbc_exec($this->dbh, "SELECT * FROM [$table]");
        $rows   = [];
        if ($result) {
            while ($row = odbc_fetch_array($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function __destruct()
    {
        if (isset($this->dbh)) {
            odbc_close($this->dbh);
        }
    }
}
