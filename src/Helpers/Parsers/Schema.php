<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers;

/**
 * The `GenericDatabase\Helpers\Parsers\Schema` class provides methods for working with Schema.ini files.
 * Schema.ini files define column types and formats for flat file databases (CSV, JSON, XML, YAML).
 *
 * Schema.ini Format Example:
 * ```
 * [users.json]
 * Format=JSONDelimited
 * Col1=id Integer
 * Col2=name Char Width 255
 * Col3=email Char Width 255
 * Col4=age Integer
 * Col5=created_at DateTime
 * Col6=active Bit
 *
 * [products.csv]
 * Format=CSVDelimited
 * CharacterSet=UTF8
 * Col1=id Integer
 * Col2=name Char Width 100
 * Col3=price Float
 * Col4=stock Integer
 * ```
 *
 * Supported Types:
 * - Bit: Boolean values (0/1, true/false)
 * - Char: String values with optional Width
 * - Integer: Integer values
 * - Float: Floating-point values
 * - Double: Double precision floating-point values
 * - DateTime: Date and time values
 * - Date: Date values
 * - Time: Time values
 * - Text: Long text values
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage Schema
 */
class Schema
{
    /**
     * Type constants for column definitions
     */
    public const TYPE_BIT = 'Bit';
    public const TYPE_CHAR = 'Char';
    public const TYPE_INTEGER = 'Integer';
    public const TYPE_FLOAT = 'Float';
    public const TYPE_DOUBLE = 'Double';
    public const TYPE_DATETIME = 'DateTime';
    public const TYPE_DATE = 'Date';
    public const TYPE_TIME = 'Time';
    public const TYPE_TEXT = 'Text';

    /**
     * Format constants
     */
    public const FORMAT_CSV_DELIMITED = 'CSVDelimited';
    public const FORMAT_TAB_DELIMITED = 'TabDelimited';
    public const FORMAT_FIXED_LENGTH = 'FixedLength';
    public const FORMAT_JSON_DELIMITED = 'JSONDelimited';
    public const FORMAT_XML_DELIMITED = 'XMLDelimited';
    public const FORMAT_YAML_DELIMITED = 'YAMLDelimited';

    /**
     * Cached schema data
     *
     * @var array
     */
    private static array $schemaCache = [];

    /**
     * Check if a Schema.ini file exists in the given directory.
     *
     * @param string $directory The directory to check.
     * @return bool True if Schema.ini exists, false otherwise.
     */
    public static function exists(string $directory): bool
    {
        $schemaPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Schema.ini';
        return file_exists($schemaPath);
    }

    /**
     * Get the Schema.ini path for a given directory.
     *
     * @param string $directory The directory path.
     * @return string The full path to Schema.ini.
     */
    public static function getPath(string $directory): string
    {
        return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Schema.ini';
    }

    /**
     * Load and parse a Schema.ini file.
     *
     * @param string $path The path to Schema.ini file.
     * @return array The parsed schema definitions indexed by file name.
     */
    public static function load(string $path): array
    {
        if (isset(self::$schemaCache[$path])) {
            return self::$schemaCache[$path];
        }

        if (!file_exists($path)) {
            return [];
        }

        $schema = [];
        $currentSection = null;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, ';') || str_starts_with($line, '#')) {
                continue;
            }

            // Section header [filename]
            if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
                $currentSection = $matches[1];
                $schema[$currentSection] = [
                    'format' => null,
                    'charset' => 'UTF8',
                    'columns' => [],
                    'settings' => []
                ];
                continue;
            }

            // Key=Value pairs
            if ($currentSection !== null && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (strtolower($key) === 'format') {
                    $schema[$currentSection]['format'] = $value;
                } elseif (strtolower($key) === 'characterset' || strtolower($key) === 'charset') {
                    $schema[$currentSection]['charset'] = $value;
                } elseif (preg_match('/^col(\d+)$/i', $key, $colMatch)) {
                    $colIndex = (int) $colMatch[1];
                    $schema[$currentSection]['columns'][$colIndex] = self::parseColumnDefinition($value);
                } else {
                    $schema[$currentSection]['settings'][$key] = $value;
                }
            }
        }

        self::$schemaCache[$path] = $schema;
        return $schema;
    }

    /**
     * Get schema for a specific file from Schema.ini.
     *
     * @param string $directory The directory containing Schema.ini.
     * @param string $filename The filename to get schema for.
     * @return array|null The schema definition or null if not found.
     */
    public static function getSchemaForFile(string $directory, string $filename): ?array
    {
        $schemaPath = self::getPath($directory);
        $schema = self::load($schemaPath);
        $section = self::resolveFileName($schema, $filename);

        if ($section !== null && isset($schema[$section])) {
            return $schema[$section];
        }

        return null;
    }

    /**
     * Resolve the schema section filename from a table name or filename.
     *
     * @param array $schema The parsed schema definitions.
     * @param string $table The table name or filename.
     * @return string|null The resolved schema section name.
     */
    public static function resolveFileName(array $schema, string $table): ?string
    {
        $table = trim($table);
        if ($table === '') {
            return null;
        }

        // Exact match
        if (isset($schema[$table])) {
            return $table;
        }

        // Try basename
        $basename = basename($table);
        if (isset($schema[$basename])) {
            return $basename;
        }

        $tableName = pathinfo($basename, PATHINFO_FILENAME);

        // Match by TableName setting
        foreach ($schema as $section => $definition) {
            $declaredTable = self::getTableNameFromDefinition($definition);
            if ($declaredTable === null) {
                continue;
            }

            if (strcasecmp($declaredTable, $table) === 0 ||
                strcasecmp($declaredTable, $basename) === 0 ||
                strcasecmp($declaredTable, $tableName) === 0
            ) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Extract TableName from schema definition settings.
     *
     * @param array $definition The schema section definition.
     * @return string|null The table name if present.
     */
    private static function getTableNameFromDefinition(array $definition): ?string
    {
        if (!isset($definition['settings']) || !is_array($definition['settings'])) {
            return null;
        }

        foreach ($definition['settings'] as $key => $value) {
            if (strtolower((string) $key) === 'tablename') {
                return trim((string) $value, "\"' ");
            }
        }

        return null;
    }

    /**
     * Parse a column definition string.
     *
     * @param string $definition The column definition (e.g., "id Integer" or "name Char Width 255").
     * @return array The parsed column definition.
     */
    public static function parseColumnDefinition(string $definition): array
    {
        $parts = preg_split('/\s+/', trim($definition));

        $column = [
            'name' => $parts[0] ?? '',
            'type' => $parts[1] ?? self::TYPE_CHAR,
            'width' => null,
            'nullable' => true,
            'default' => null
        ];

        // Parse additional attributes
        $i = 2;
        while ($i < count($parts)) {
            $attr = strtolower($parts[$i]);

            if ($attr === 'width' && isset($parts[$i + 1])) {
                $column['width'] = (int) $parts[$i + 1];
                $i += 2;
            } elseif ($attr === 'notnull' || $attr === 'required') {
                $column['nullable'] = false;
                $i++;
            } elseif ($attr === 'default' && isset($parts[$i + 1])) {
                $column['default'] = $parts[$i + 1];
                $i += 2;
            } else {
                $i++;
            }
        }

        return $column;
    }

    /**
     * Cast a value to the appropriate PHP type based on column type.
     *
     * @param mixed $value The value to cast.
     * @param string $type The column type.
     * @return mixed The casted value.
     */
    public static function castValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match (strtolower($type)) {
            'bit', 'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'single' => (float) $value,
            'double', 'real' => (float) $value,
            'datetime' => self::parseDateTime($value),
            'date' => self::parseDate($value),
            'time' => self::parseTime($value),
            default => (string) $value
        };
    }

    /**
     * Parse a datetime value.
     *
     * @param mixed $value The value to parse.
     * @return string|null The parsed datetime or null.
     */
    private static function parseDateTime(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    /**
     * Parse a date value.
     *
     * @param mixed $value The value to parse.
     * @return string|null The parsed date or null.
     */
    private static function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * Parse a time value.
     *
     * @param mixed $value The value to parse.
     * @return string|null The parsed time or null.
     */
    private static function parseTime(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp !== false ? date('H:i:s', $timestamp) : null;
    }

    /**
     * Apply schema types to an array of rows.
     *
     * @param array $rows The rows to process.
     * @param array $schema The schema definition with column types.
     * @return array The rows with properly typed values.
     */
    public static function applySchema(array $rows, array $schema): array
    {
        if (empty($schema['columns'])) {
            return $rows;
        }

        $columnTypes = [];
        foreach ($schema['columns'] as $col) {
            $columnTypes[$col['name']] = $col['type'];
        }

        $result = [];
        foreach ($rows as $row) {
            $typedRow = [];
            foreach ($row as $key => $value) {
                $type = $columnTypes[$key] ?? self::TYPE_CHAR;
                $typedRow[$key] = self::castValue($value, $type);
            }
            $result[] = $typedRow;
        }

        return $result;
    }

    /**
     * Generate a Schema.ini entry for a file based on data analysis.
     *
     * @param string $filename The filename.
     * @param array $data Sample data to analyze.
     * @param string $format The format type.
     * @return string The Schema.ini entry content.
     */
    public static function generateSchemaEntry(string $filename, array $data, string $format = self::FORMAT_JSON_DELIMITED): string
    {
        $output = "[$filename]\n";
        $output .= "Format=$format\n";
        $output .= "CharacterSet=UTF8\n";

        if (empty($data)) {
            return $output;
        }

        $firstRow = reset($data);
        if (!is_array($firstRow)) {
            return $output;
        }

        $colIndex = 1;
        foreach ($firstRow as $key => $value) {
            $type = self::inferType($value, $data, $key);
            $output .= "Col{$colIndex}=$key $type\n";
            $colIndex++;
        }

        return $output;
    }

    /**
     * Infer the type of a column based on sample values.
     *
     * @param mixed $sampleValue A sample value.
     * @param array $allData All data for more accurate inference.
     * @param string $columnName The column name.
     * @return string The inferred type with optional width.
     */
    public static function inferType(mixed $sampleValue, array $allData, string $columnName): string
    {
        // Collect all values for this column
        $values = array_filter(array_column($allData, $columnName), fn($v) => $v !== null && $v !== '');

        if (empty($values)) {
            return self::TYPE_CHAR . ' Width 255';
        }

        // Check for boolean
        $boolCount = 0;
        foreach ($values as $v) {
            if (is_bool($v) || in_array(strtolower((string) $v), ['true', 'false', '0', '1'], true)) {
                $boolCount++;
            }
        }
        if ($boolCount === count($values)) {
            return self::TYPE_BIT;
        }

        // Check for integer
        $intCount = 0;
        foreach ($values as $v) {
            if (is_int($v) || (is_string($v) && ctype_digit(ltrim($v, '-')))) {
                $intCount++;
            }
        }
        if ($intCount === count($values)) {
            return self::TYPE_INTEGER;
        }

        // Check for float
        $floatCount = 0;
        foreach ($values as $v) {
            if (is_float($v) || (is_numeric($v) && str_contains((string) $v, '.'))) {
                $floatCount++;
            }
        }
        if ($floatCount === count($values)) {
            return self::TYPE_FLOAT;
        }

        // Check for datetime patterns
        $dateTimeCount = 0;
        foreach ($values as $v) {
            if (is_string($v) && strtotime($v) !== false) {
                if (preg_match('/\d{4}-\d{2}-\d{2}/', $v)) {
                    $dateTimeCount++;
                }
            }
        }
        if ($dateTimeCount === count($values)) {
            return self::TYPE_DATETIME;
        }

        // Default to string - calculate max width
        $maxWidth = 0;
        foreach ($values as $v) {
            $len = strlen((string) $v);
            if ($len > $maxWidth) {
                $maxWidth = $len;
            }
        }

        // Round up to common width
        if ($maxWidth <= 50) {
            $maxWidth = 50;
        } elseif ($maxWidth <= 100) {
            $maxWidth = 100;
        } elseif ($maxWidth <= 255) {
            $maxWidth = 255;
        } else {
            return self::TYPE_TEXT;
        }

        return self::TYPE_CHAR . " Width $maxWidth";
    }

    /**
     * Clear the schema cache.
     *
     * @param string|null $path Optional specific path to clear, or null to clear all.
     * @return void
     */
    public static function clearCache(?string $path = null): void
    {
        if ($path !== null) {
            unset(self::$schemaCache[$path]);
        } else {
            self::$schemaCache = [];
        }
    }

    /**
     * Save schema to a Schema.ini file.
     *
     * @param string $path The path to save the Schema.ini file.
     * @param array $schema The schema definitions to save.
     * @return bool True on success, false on failure.
     */
    public static function save(string $path, array $schema): bool
    {
        $content = "";

        foreach ($schema as $filename => $definition) {
            $content .= "[$filename]\n";

            if (!empty($definition['format'])) {
                $content .= "Format={$definition['format']}\n";
            }

            if (!empty($definition['charset'])) {
                $content .= "CharacterSet={$definition['charset']}\n";
            }

            if (!empty($definition['settings'])) {
                foreach ($definition['settings'] as $key => $value) {
                    $content .= "$key=$value\n";
                }
            }

            if (!empty($definition['columns'])) {
                $colIndex = 1;
                foreach ($definition['columns'] as $col) {
                    $colDef = $col['name'] . ' ' . $col['type'];
                    if (!empty($col['width'])) {
                        $colDef .= ' Width ' . $col['width'];
                    }
                    if (isset($col['nullable']) && !$col['nullable']) {
                        $colDef .= ' NotNull';
                    }
                    if (isset($col['default'])) {
                        $colDef .= ' Default ' . $col['default'];
                    }
                    $content .= "Col{$colIndex}=$colDef\n";
                    $colIndex++;
                }
            }

            $content .= "\n";
        }

        self::clearCache($path);
        return file_put_contents($path, $content) !== false;
    }
}

