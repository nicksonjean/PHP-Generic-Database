<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * Firebird Exporter
 * Exports Firebird/Interbase database tables to different formats using ibase extension
 */
class FirebirdExporter extends BaseExporter
{
    /** @var resource|false */
    protected $dbh;
    protected string $database;

    /**
     * Constructor
     *
     * @param string $host Database host
     * @param string $user Database user
     * @param string $password Database password
     * @param string $database Database path or alias
     * @param int $port Database port (default: 3050)
     * @param string $charset Database charset (default: UTF8)
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        string $outputPath,
        int $port = 3050,
        string $charset = 'UTF8'
    ) {
        if (!extension_loaded('interbase')) {
            throw new Exception("Interbase extension is not available. Please install and enable the interbase PHP extension.");
        }

        $this->database = $database;
        $dsn = "{$host}/{$port}:{$database}";
        $connection = @ibase_connect($dsn, $user, $password, $charset);

        // ibase_connect returns false on failure, or a resource on success
        if (!is_resource($connection)) {
            throw new Exception("Firebird connection failed: " . ibase_errmsg());
        }

        /** @var resource $connection */
        $this->dbh = $connection;

        parent::__construct($outputPath);
    }

    /**
     * Load all table names from database
     */
    protected function loadTables(): void
    {
        $query = "SELECT RDB\$RELATION_NAME FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$RELATION_TYPE = 0";
        $result = ibase_query($this->dbh, $query);

        if ($result !== false) {
            while ($row = ibase_fetch_assoc($result)) {
                $tableName = trim($row['RDB$RELATION_NAME']);
                $this->tables[] = $tableName;
            }
            ibase_free_result($result);
        }
    }

    /**
     * Load schema information for all tables
     */
    protected function loadTableSchemas(): void
    {
        foreach ($this->tables as $table) {
            $tableEscaped = str_replace("'", "''", $table);
            $query = "
                SELECT 
                    r.RDB\$FIELD_NAME as column_name,
                    f.RDB\$FIELD_TYPE as field_type,
                    f.RDB\$NULL_FLAG as nullable,
                    r.RDB\$DEFAULT_SOURCE as default_value
                FROM RDB\$RELATION_FIELDS r
                JOIN RDB\$FIELDS f ON r.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
                WHERE r.RDB\$RELATION_NAME = '{$tableEscaped}'
                ORDER BY r.RDB\$FIELD_POSITION
            ";

            $result = ibase_query($this->dbh, $query);
            $columns = [];
            $primaryKey = null;

            if ($result !== false) {
                while ($row = ibase_fetch_assoc($result)) {
                    $columnName = trim($row['COLUMN_NAME']);
                    $isNullable = isset($row['NULLABLE']) && $row['NULLABLE'] != 0;

                    $columns[] = [
                        'name' => $columnName,
                        'type' => $this->normalizeFirebirdType($row['FIELD_TYPE']),
                        'notnull' => !$isNullable,
                        'dflt_value' => $row['DEFAULT_VALUE'] ?? null,
                        'pk' => false // Will be determined separately
                    ];
                }
                ibase_free_result($result);
            }

            // Get primary key
            $pkQuery = "
                SELECT s.RDB\$FIELD_NAME
                FROM RDB\$INDEX_SEGMENTS s
                JOIN RDB\$INDICES i ON s.RDB\$INDEX_NAME = i.RDB\$INDEX_NAME
                WHERE i.RDB\$RELATION_NAME = '{$tableEscaped}' AND i.RDB\$UNIQUE_FLAG = 1
            ";
            $pkResult = ibase_query($this->dbh, $pkQuery);
            if ($pkResult !== false) {
                $pkRow = ibase_fetch_assoc($pkResult);
                if ($pkRow !== false) {
                    $primaryKey = trim($pkRow['RDB$FIELD_NAME']);
                }
                ibase_free_result($pkResult);
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
     * Normalize Firebird type to standard type
     *
     * @param int $fieldType Firebird field type constant
     * @return string Normalized type
     */
    protected function normalizeFirebirdType(int $fieldType): string
    {
        // Firebird field types: 7=SMALLINT, 8=INTEGER, 10=FLOAT, 12=DATE, 13=TIME, 14=CHAR, 16=BIGINT, 27=DOUBLE, 35=TIMESTAMP, 37=VARCHAR, 261=BLOB
        return match ($fieldType) {
            7, 8, 16 => 'Integer',
            10, 27 => 'Text', // Float/Double
            12, 13, 35 => 'DateTime',
            default => 'Text'
        };
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
        $result = ibase_query($this->dbh, $query);
        $data = [];

        if ($result !== false) {
            while ($row = ibase_fetch_assoc($result)) {
                // Trim all keys and values
                $normalizedRow = [];
                foreach ($row as $key => $value) {
                    $normalizedRow[trim($key)] = is_string($value) ? trim($value) : $value;
                }
                $data[] = $normalizedRow;
            }
            ibase_free_result($result);
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
                    s1.RDB\$FIELD_NAME as 'from',
                    i2.RDB\$RELATION_NAME as 'table',
                    s2.RDB\$FIELD_NAME as 'to'
                FROM RDB\$RELATION_CONSTRAINTS rc1
                JOIN RDB\$INDEX_SEGMENTS s1 ON rc1.RDB\$INDEX_NAME = s1.RDB\$INDEX_NAME
                JOIN RDB\$REF_CONSTRAINTS ref ON rc1.RDB\$CONSTRAINT_NAME = ref.RDB\$CONSTRAINT_NAME
                JOIN RDB\$RELATION_CONSTRAINTS rc2 ON ref.RDB\$CONST_NAME_UQ = rc2.RDB\$CONSTRAINT_NAME
                JOIN RDB\$INDICES i2 ON rc2.RDB\$INDEX_NAME = i2.RDB\$INDEX_NAME
                JOIN RDB\$INDEX_SEGMENTS s2 ON i2.RDB\$INDEX_NAME = s2.RDB\$INDEX_NAME AND s1.RDB\$FIELD_POSITION = s2.RDB\$FIELD_POSITION
                WHERE rc1.RDB\$RELATION_NAME = ? AND rc1.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
            ";

            // Note: ibase_prepare with parameters is not well supported
            // Using direct query instead for Firebird foreign keys
            $tableEscaped = str_replace("'", "''", $table);
            $query = str_replace('?', "'{$tableEscaped}'", $query);
            $result = ibase_query($this->dbh, $query);
            if ($result !== false) {
                while ($row = ibase_fetch_assoc($result)) {
                    $foreignKeys[] = [
                        'from' => trim($row['FROM']),
                        'table' => trim($row['TABLE']),
                        'to' => trim($row['TO'] ?? 'id')
                    ];
                }
                ibase_free_result($result);
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
            ibase_close($this->dbh);
        }
    }
}
