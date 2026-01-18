<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * Base abstract class for all exporters
 * Provides common functionality and defines abstract methods for database-specific implementations
 */
abstract class BaseExporter
{
    protected string $outputPath;
    protected array $tables = [];
    protected array $tableSchemas = [];

    /**
     * Constructor
     *
     * @param string $outputPath Path where exported files will be saved
     * @throws Exception
     */
    public function __construct(string $outputPath)
    {
        $this->outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Create output directory if it doesn't exist
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }

        $this->loadTables();
        $this->loadTableSchemas();
    }

    /**
     * Load all table names from database
     * Must be implemented by each engine-specific exporter
     *
     * @return void
     * @throws Exception
     */
    abstract protected function loadTables(): void;

    /**
     * Load schema information for all tables
     * Must be implemented by each engine-specific exporter
     *
     * @return void
     * @throws Exception
     */
    abstract protected function loadTableSchemas(): void;

    /**
     * Normalize database-specific type to standard type
     *
     * @param string|null $type Database-specific type
     * @return string Normalized type
     */
    protected function normalizeType(?string $type): string
    {
        if (empty($type)) {
            return 'Text';
        }

        $type = strtoupper(trim($type));

        // Integer types
        if (in_array($type, ['INTEGER', 'INT', 'BIGINT', 'SMALLINT', 'TINYINT', 'MEDIUMINT', 'SERIAL', 'BIGSERIAL'])) {
            return 'Integer';
        }

        // Real/Float types
        if (in_array($type, ['REAL', 'DOUBLE', 'FLOAT', 'NUMERIC', 'DECIMAL', 'MONEY', 'SMALLMONEY'])) {
            return 'Text'; // Schema.ini uses Text for decimal
        }

        // DateTime types
        if (in_array($type, ['DATETIME', 'TIMESTAMP', 'DATE', 'TIME', 'DATETIME2', 'SMALLDATETIME'])) {
            return 'DateTime';
        }

        // Boolean types
        if (in_array($type, ['BOOLEAN', 'BOOL', 'BIT'])) {
            return 'Text'; // Schema.ini uses Text for boolean
        }

        // Default to Text
        return 'Text';
    }

    /**
     * Get all data from a table
     * Must be implemented by each engine-specific exporter
     *
     * @param string $table Table name
     * @return array Array of rows
     * @throws Exception
     */
    abstract public function getTableData(string $table): array;

    /**
     * Clean string removing BOM and special characters
     *
     * @param string $str String to clean
     * @return string Cleaned string
     */
    public function cleanString(string $str): string
    {
        // Remove BOM UTF-8 (0xEF 0xBB 0xBF)
        $str = preg_replace('/^\xEF\xBB\xBF/', '', $str);
        // Remove other common BOMs
        $str = preg_replace('/^\xFE\xFF|\xFF\xFE|\x00\x00\xFE\xFF|\xFF\xFE\x00\x00/', '', $str);
        // Remove invisible control characters
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
        return trim($str);
    }

    /**
     * Sanitize name for XML element
     *
     * @param string $name Name to sanitize
     * @return string Sanitized name
     */
    public function sanitizeXmlName(string $name): string
    {
        $name = $this->cleanString($name);

        // Special case: "id" should remain as "id"
        if (strtolower($name) === 'id') {
            return 'id';
        }

        // Cannot start with number (except if it's "id")
        $name = preg_replace('/^([0-9])/', '_$1', $name);

        // Replace invalid characters with _
        $name = preg_replace('/[^a-zA-Z0-9_\-:.]/', '_', $name);

        // If empty, use default name
        if (empty($name)) {
            $name = 'field';
        }

        return $name;
    }

    /**
     * Get foreign keys for a table
     * Must be implemented by each engine-specific exporter
     *
     * @param string $table Table name
     * @return array Array of foreign keys
     */
    public function getForeignKeys(string $table): array
    {
        // Default implementation uses heuristics based on naming conventions
        return $this->detectForeignKeysByConvention($table);
    }

    /**
     * Detect foreign keys based on naming conventions
     * Columns ending with _id are assumed to be foreign keys
     *
     * @param string $table Table name
     * @return array Array of foreign keys
     */
    protected function detectForeignKeysByConvention(string $table): array
    {
        $foreignKeys = [];

        if (!isset($this->tableSchemas[$table])) {
            return $foreignKeys;
        }

        $schema = $this->tableSchemas[$table];
        $allTables = $this->tables;

        foreach ($schema['columns'] as $column) {
            $columnName = $column['name'];

            // Skip if it's the primary key
            if ($columnName === $schema['primaryKey']) {
                continue;
            }

            // Check if column name ends with _id (common foreign key convention)
            if (preg_match('/^(.+)_id$/', $columnName, $matches)) {
                $referencedTableName = $matches[1];

                // Try to find matching table - prioritize exact matches first
                $matchedTable = null;

                // First, try exact match (most common case)
                if (in_array($referencedTableName, $allTables)) {
                    $matchedTable = $referencedTableName;
                }
                // Try with underscore variations (e.g., "card_sector" matches "sector")
                elseif (str_ends_with($referencedTableName, '_sector') && in_array('card_sector', $allTables)) {
                    $matchedTable = 'card_sector';
                }
                // Try reverse: "sector" matches "card_sector" when column is "sector_id"
                else {
                    foreach ($allTables as $otherTable) {
                        // Check if table name ends with the referenced name
                        if (str_ends_with($otherTable, '_' . $referencedTableName) ||
                            str_ends_with($otherTable, $referencedTableName)) {
                            $matchedTable = $otherTable;
                            break;
                        }
                        // Check if referenced name is part of table name
                        if (str_contains($otherTable, $referencedTableName) && 
                            $otherTable !== $table) {
                            $matchedTable = $otherTable;
                            break;
                        }
                    }
                }

                // Special cases for common patterns
                if (!$matchedTable) {
                    // Handle cases like "usuario_dados_id" -> "usuario_dados"
                    if (in_array($referencedTableName . '_dados', $allTables)) {
                        $matchedTable = $referencedTableName . '_dados';
                    }
                    // Handle cases like "card_activity" -> "card"
                    elseif (str_starts_with($referencedTableName, 'card_') && in_array('card', $allTables)) {
                        $matchedTable = 'card';
                    }
                    // Try plural/singular variations as last resort
                    else {
                        foreach ($allTables as $otherTable) {
                            if ($otherTable === $referencedTableName . 's' || 
                                $referencedTableName === $otherTable . 's' ||
                                $otherTable === $referencedTableName . 'es' ||
                                $referencedTableName === $otherTable . 'es') {
                                $matchedTable = $otherTable;
                                break;
                            }
                        }
                    }
                }

                if ($matchedTable && $matchedTable !== $table) {
                    // Get primary key of referenced table
                    $referencedPrimaryKey = 'id';
                    if (isset($this->tableSchemas[$matchedTable]['primaryKey'])) {
                        $referencedPrimaryKey = $this->tableSchemas[$matchedTable]['primaryKey'];
                    }

                    $foreignKeys[] = [
                        'from' => $columnName,
                        'table' => $matchedTable,
                        'to' => $referencedPrimaryKey
                    ];
                }
            }
        }

        return $foreignKeys;
    }

    /**
     * Get output path
     *
     * @return string Output path
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * Set output path
     *
     * @param string $outputPath New output path
     * @return void
     */
    public function setOutputPath(string $outputPath): void
    {
        $this->outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }

    /**
     * Get tables
     *
     * @return array Array of table names
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Get table schemas
     *
     * @return array Array of table schemas
     */
    public function getTableSchemas(): array
    {
        return $this->tableSchemas;
    }
}

