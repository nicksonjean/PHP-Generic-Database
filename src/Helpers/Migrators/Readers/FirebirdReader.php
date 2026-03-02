<?php

namespace GenericDatabase\Helpers\Migrators\Readers;

use Exception;

/**
 * Source reader for Firebird / Interbase databases using the ibase extension.
 */
class FirebirdReader extends BaseMigrator
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
        int $port = 3050,
        string $charset = 'UTF8'
    ) {
        if (!extension_loaded('interbase')) {
            throw new Exception(
                'Interbase/Firebird extension is not available. '
                . 'Please install and enable the interbase PHP extension.'
            );
        }

        $connStr = "{$host}/{$port}:{$database}";
        $conn    = ibase_connect($connStr, $user, $password, $charset);

        if (!$conn) {
            throw new Exception('Firebird connection failed: ' . ibase_errmsg());
        }

        $this->dbh = $conn;

        $this->loadTables();
        $this->loadTableSchemas();
    }

    public function getSourceEngine(): string
    {
        return 'firebird';
    }

    protected function loadTables(): void
    {
        $result = ibase_query(
            $this->dbh,
            "SELECT TRIM(RDB\$RELATION_NAME) AS name
             FROM RDB\$RELATIONS
             WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$VIEW_BLR IS NULL
             ORDER BY RDB\$RELATION_NAME"
        );
        while ($row = ibase_fetch_assoc($result)) {
            $this->tables[] = strtolower(trim($row['NAME']));
        }
        ibase_free_result($result);
    }

    private function firebirdFieldTypeToSQL(int $fieldType, int $fieldSubType = 0): string
    {
        return match ($fieldType) {
            7   => 'SMALLINT',
            8   => 'INTEGER',
            10  => 'FLOAT',
            12  => 'DATE',
            13  => 'TIME',
            14  => 'CHAR',
            16  => 'BIGINT',
            27  => 'DOUBLE PRECISION',
            35  => 'TIMESTAMP',
            37  => 'VARCHAR',
            40  => 'CSTRING',
            261 => ($fieldSubType === 1 ? 'TEXT' : 'BLOB'),
            default => 'VARCHAR',
        };
    }

    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $upperTable = strtoupper($table);

            $result = ibase_query(
                $this->dbh,
                "SELECT
                    TRIM(rf.RDB\$FIELD_NAME) AS col_name,
                    f.RDB\$FIELD_TYPE       AS field_type,
                    f.RDB\$FIELD_SUB_TYPE   AS field_sub_type,
                    f.RDB\$FIELD_LENGTH     AS field_length,
                    rf.RDB\$NULL_FLAG       AS not_null,
                    rf.RDB\$DEFAULT_SOURCE  AS dflt_value
                 FROM RDB\$RELATION_FIELDS rf
                 JOIN RDB\$FIELDS f ON f.RDB\$FIELD_NAME = rf.RDB\$FIELD_SOURCE
                 WHERE rf.RDB\$RELATION_NAME = '$upperTable'
                 ORDER BY rf.RDB\$FIELD_POSITION"
            );

            $pkResult = ibase_query(
                $this->dbh,
                "SELECT TRIM(ucc.RDB\$FIELD_NAME) AS pk_col
                 FROM RDB\$RELATION_CONSTRAINTS rc
                 JOIN RDB\$INDEX_SEGMENTS ucc ON rc.RDB\$INDEX_NAME = ucc.RDB\$INDEX_NAME
                 WHERE rc.RDB\$RELATION_NAME = '$upperTable'
                   AND rc.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'"
            );

            $pkColumns = [];
            while ($row = ibase_fetch_assoc($pkResult)) {
                $pkColumns[] = strtolower(trim($row['PK_COL']));
            }
            ibase_free_result($pkResult);

            $columns    = [];
            $primaryKey = null;

            while ($row = ibase_fetch_assoc($result)) {
                $colName   = strtolower(trim($row['COL_NAME']));
                $fieldType = (int)$row['FIELD_TYPE'];
                $subType   = (int)($row['FIELD_SUB_TYPE'] ?? 0);
                $length    = (int)($row['FIELD_LENGTH'] ?? 0);
                $sqlType   = $this->firebirdFieldTypeToSQL($fieldType, $subType);

                $displayType = $sqlType;
                if (in_array($sqlType, ['VARCHAR', 'CHAR', 'CSTRING'], true) && $length > 0) {
                    $displayType .= "($length)";
                }

                $isPk = in_array($colName, $pkColumns, true);

                $dfltRaw = $row['DFLT_VALUE'] !== null ? trim((string)$row['DFLT_VALUE']) : null;
                if ($dfltRaw !== null && str_starts_with(strtoupper($dfltRaw), 'DEFAULT ')) {
                    $dfltRaw = substr($dfltRaw, 8);
                }

                $columns[] = [
                    'name'       => $colName,
                    'raw_type'   => $displayType,
                    'canonical'  => $this->normalizeToCanonical($sqlType),
                    'notnull'    => (bool)$row['NOT_NULL'],
                    'dflt_value' => $dfltRaw,
                    'pk'         => $isPk,
                ];

                if ($isPk && $primaryKey === null) {
                    $primaryKey = $colName;
                }
            }

            ibase_free_result($result);

            $this->tableSchemas[$table] = [
                'columns'    => $columns,
                'primaryKey' => $primaryKey,
            ];
        }
    }

    public function getTableData(string $table): array
    {
        $upperTable = strtoupper($table);
        $result     = ibase_query($this->dbh, "SELECT * FROM \"$upperTable\"");
        $rows       = [];
        while ($row = ibase_fetch_assoc($result)) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[strtolower(trim($key))] = is_string($value) ? trim($value) : $value;
            }
            $rows[] = $normalized;
        }
        ibase_free_result($result);
        return $rows;
    }

    public function __destruct()
    {
        if (isset($this->dbh)) {
            ibase_close($this->dbh);
        }
    }
}
