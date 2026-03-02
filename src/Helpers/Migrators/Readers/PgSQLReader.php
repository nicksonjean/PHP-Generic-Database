<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use Exception;

/**
 * Source reader for PostgreSQL databases using the pgsql extension.
 */
class PgSQLReader extends BaseMigrator
{
    /** @var resource */
    protected $dbh;

    /**
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 5432
    ) {
        if (!extension_loaded('pgsql')) {
            throw new Exception(
                'pgsql extension is not available. '
                . 'Please install and enable the pgsql PHP extension.'
            );
        }

        $connStr = "host=$host port=$port dbname=$database user=$user password=$password";
        $conn    = pg_connect($connStr);

        if ($conn === false) {
            throw new Exception("PostgreSQL connection failed for database '{$database}'.");
        }

        $this->dbh = $conn;

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'pgsql';
    }

    protected function loadTables(): void
    {
        $result = pg_query(
            $this->dbh,
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        );
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $this->tables[] = $row['tablename'];
            }
            pg_free_result($result);
        }
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $colResult = pg_query_params(
                $this->dbh,
                "SELECT column_name, data_type, character_maximum_length,
                        numeric_precision, numeric_scale,
                        is_nullable, column_default
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = $1
                 ORDER BY ordinal_position",
                [$table]
            );

            $pkResult = pg_query_params(
                $this->dbh,
                "SELECT a.attname AS column_name
                 FROM pg_index i
                 JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                 WHERE i.indrelid = $1::regclass AND i.indisprimary",
                ["\"$table\""]
            );

            $pkColumns = [];
            if ($pkResult) {
                while ($row = pg_fetch_assoc($pkResult)) {
                    $pkColumns[] = $row['column_name'];
                }
                pg_free_result($pkResult);
            }

            $columns    = [];
            $primaryKey = null;

            if ($colResult) {
                while ($row = pg_fetch_assoc($colResult)) {
                    $rawType     = $row['data_type'];
                    $isPk        = in_array($row['column_name'], $pkColumns, true);
                    $displayType = strtoupper($rawType);
                    if (!empty($row['character_maximum_length'])) {
                        $displayType .= '(' . $row['character_maximum_length'] . ')';
                    } elseif (!empty($row['numeric_precision']) && !empty($row['numeric_scale'])) {
                        $displayType .= '(' . $row['numeric_precision'] . ',' . $row['numeric_scale'] . ')';
                    }

                    $columns[] = [
                        'name'       => $row['column_name'],
                        'raw_type'   => $displayType,
                        'canonical'  => $this->normalizeToCanonical($rawType),
                        'notnull'    => $row['is_nullable'] === 'NO',
                        'dflt_value' => $row['column_default'],
                        'pk'         => $isPk,
                    ];

                    if ($isPk && $primaryKey === null) {
                        $primaryKey = $row['column_name'];
                    }
                }
                pg_free_result($colResult);
            }

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    public function getTableData(string $table): array
    {
        $result = pg_query($this->dbh, "SELECT * FROM \"$table\"");
        $rows   = [];
        if ($result) {
            while ($row = pg_fetch_assoc($result)) {
                $rows[] = $row;
            }
            pg_free_result($result);
        }
        return $rows;
    }

    public function __destruct()
    {
        if (isset($this->dbh)) {
            pg_close($this->dbh);
        }
    }
}
