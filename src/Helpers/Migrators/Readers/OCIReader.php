<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use Exception;

/**
 * Source reader for Oracle databases using the OCI8 extension.
 */
class OCIReader extends BaseMigrator
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
        int $port = 1521,
        string $charset = 'AL32UTF8'
    ) {
        if (!extension_loaded('oci8')) {
            throw new Exception(
                'OCI8 extension is not available. '
                . 'Please install and enable the oci8 PHP extension.'
            );
        }

        $connStr = "//{$host}:{$port}/{$database}";
        $conn    = oci_connect($user, $password, $connStr, $charset);

        if (!$conn) {
            $err = oci_error();
            throw new Exception('OCI8 connection failed: ' . ($err['message'] ?? 'unknown error'));
        }

        $this->dbh = $conn;

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'oracle';
    }

    protected function loadTables(): void
    {
        $stmt = oci_parse($this->dbh, 'SELECT table_name FROM user_tables ORDER BY table_name');
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $this->tables[] = strtolower($row['TABLE_NAME']);
        }
        oci_free_statement($stmt);
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $upperTable = strtoupper($table);

            $stmt = oci_parse(
                $this->dbh,
                "SELECT column_name, data_type, data_length, data_precision, data_scale,
                        nullable, data_default
                 FROM user_tab_columns
                 WHERE table_name = :tbl
                 ORDER BY column_id"
            );
            oci_bind_by_name($stmt, ':tbl', $upperTable);
            oci_execute($stmt);

            $pkStmt = oci_parse(
                $this->dbh,
                "SELECT ucc.column_name
                 FROM user_constraints uc
                 JOIN user_cons_columns ucc ON uc.constraint_name = ucc.constraint_name
                 WHERE uc.constraint_type = 'P' AND uc.table_name = :tbl"
            );
            oci_bind_by_name($pkStmt, ':tbl', $upperTable);
            oci_execute($pkStmt);

            $pkColumns = [];
            while ($row = oci_fetch_assoc($pkStmt)) {
                $pkColumns[] = strtolower($row['COLUMN_NAME']);
            }
            oci_free_statement($pkStmt);

            $columns    = [];
            $primaryKey = null;

            while ($row = oci_fetch_assoc($stmt)) {
                $colName = strtolower($row['COLUMN_NAME']);
                $rawType = $row['DATA_TYPE'];

                $displayType = strtoupper($rawType);
                if (!empty($row['DATA_PRECISION']) && !empty($row['DATA_SCALE'])) {
                    $displayType .= '(' . $row['DATA_PRECISION'] . ',' . $row['DATA_SCALE'] . ')';
                } elseif (!empty($row['DATA_LENGTH']) && in_array(strtoupper($rawType), ['VARCHAR2', 'CHAR', 'NVARCHAR2', 'NCHAR'], true)) {
                    $displayType .= '(' . $row['DATA_LENGTH'] . ')';
                }

                $isPk = in_array($colName, $pkColumns, true);

                $columns[] = [
                    'name'       => $colName,
                    'raw_type'   => $displayType,
                    'canonical'  => $this->normalizeToCanonical($rawType),
                    'notnull'    => $row['NULLABLE'] === 'N',
                    'dflt_value' => $row['DATA_DEFAULT'] !== null ? trim($row['DATA_DEFAULT']) : null,
                    'pk'         => $isPk,
                ];

                if ($isPk && $primaryKey === null) {
                    $primaryKey = $colName;
                }
            }

            oci_free_statement($stmt);

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    public function getTableData(string $table): array
    {
        $stmt = oci_parse($this->dbh, 'SELECT * FROM "' . strtoupper($table) . '"');
        oci_execute($stmt);

        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[strtolower($key)] = $value;
            }
            $rows[] = $normalized;
        }

        oci_free_statement($stmt);
        return $rows;
    }

    public function __destruct()
    {
        if (isset($this->dbh)) {
            oci_close($this->dbh);
        }
    }
}
