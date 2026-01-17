<?php

namespace GenericDatabase\Helpers\Parsers;

/**
 * The `GenericDatabase\Helpers\Parsers\TXT` class provides methods for parsing, analyzing, and writing TXT and CSV files.
 *
 * Main funcionalities:
 * - Checks if all columns in the given data are null
 * - Uses the structure method to generate the schema information
 * - Analyze method to determine the column types.
 *
 * Methods:
 * - `allColumnsNull($data)`: Checks if all columns in the given data are null.
 * - `parse($filePath)`: Parses a CSV file at the given file path and returns an array of data.
 * - `analyze($data)`: Analyzes the given data and returns an array of column types (e.g., integer, float, datetime, string).
 * - `structure($filePath, $separator)`: Returns an array of schema information for CSV files in the given folder path, using the given separator.
 * - `write($overwrite = false)`: Writes schema information to a file named "Schemas.ini" in the folder path, overwriting the file if specified.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage TXT
 */
class TXT
{
    /**
     * @var string $folderPath The path to the folder containing CSV files.
     */
    private static string $folderPath;

    /**
     * @var string $separator The separator used in the CSV files.
     */
    private static string $separator = ';';

    /**
     * Checks if all columns in the provided data are null.
     *
     * @param array $data The data to check.
     * @return bool True if all columns are null, false otherwise.
     */
    private static function allColumnsNull(array $data): bool
    {
        foreach ($data as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Parses a CSV file and returns its data as an array.
     *
     * @param string $filePath The path to the CSV file.
     * @return array The parsed data.
     */
    public static function parse(string $filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, self::$separator);
            if (($row = fgetcsv($handle, 0, self::$separator)) !== false) {
                while ($row !== false) {
                    $rowData = [];
                    foreach ($header as $index => $columnName) {
                        $rowData[$columnName] = $row[$index] ?? null;
                    }
                    $data[] = $rowData;
                    $row = fgetcsv($handle, 0, self::$separator);
                }
            } else {
                foreach ($header as $columnName) {
                    $data[] = [$columnName => null];
                }
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Analyzes the data and determines the type of each column.
     *
     * @param array $data The data to analyze.
     * @return array An associative array where keys are column names and values are their types.
     */
    private static function analyze(array $data): array
    {
        $types = [];
        if (self::allColumnsNull($data)) {
            foreach ($data as $value) {
                $types[array_keys($value)[0]] = 'string';
            }
            return $types;
        }
        foreach ($data[0] as $columnName => $value) {
            $types[$columnName] = 'string';
        }
        foreach ($data as $row) {
            foreach ($row as $columnName => $value) {
                if (is_numeric($value) && (int) $value == $value) {
                    if ($types[$columnName] != 'float') {
                        $types[$columnName] = 'integer';
                    }
                } elseif (is_float($value)) {
                    $types[$columnName] = 'float';
                } elseif (strtotime($value) !== false && preg_match('/[^a-zA-Z\s{1,}]/', $value)) {
                    $types[$columnName] = 'datetime';
                }
            }
        }
        return $types;
    }

    /**
     * Generates the structure of the CSV files in the specified folder.
     *
     * @param string $filePath The path to the folder containing CSV files.
     * @param string $separator The separator used in the CSV files.
     * @return array An associative array where keys are file names and values are arrays of column schemas.
     */
    public static function structure(string $filePath, string $separator): array
    {
        self::$folderPath = $filePath;
        self::$separator = $separator;
        $files = glob(self::$folderPath . '\*.csv');
        $schemas = [];
        foreach ($files as $file) {
            $data = self::parse($file);
            $types = self::analyze($data);
            $schema = [];
            foreach ($types as $columnName => $type) {
                $schema[] = ['name' => $columnName, 'type' => $type];
            }
            $schemas[basename($file)] = $schema;
        }
        return $schemas;
    }

    /**
     * Writes the schema information to a Schemas.ini file.
     *
     * @param bool $overwrite Whether to overwrite the existing Schemas.ini file.
     * @return void
     */
    public static function write(bool $overwrite = false): void
    {
        $schemaFilePath = self::$folderPath . '\Schemas.ini';
        if (!file_exists($schemaFilePath) || $overwrite) {
            $structure = self::structure(self::$folderPath, self::$separator);
            $output = '';
            foreach ($structure as $filename => $columns) {
                $output .= "[$filename]\n";
                $output .= "Format=Delimited(;) \n";
                $output .= "ColNameHeader=True\n";
                foreach ($columns as $index => $column) {
                    $regexParts = [
                        "/([\x{00}-\x{7E}]|",
                        "[\x{C2}-\x{DF}][\x{80}-\x{BF}]|",
                        "\x{E0}[\x{A0}-\x{BF}][\x{80}-\x{BF}]|",
                        "[\x{E1}-\x{EC}\x{EE}\{xEF}][\x{80}-\x{BF}]{2}|",
                        "\x{ED}[\x{80}-\x{9F}][\x{80}-\x{BF}]|",
                        "\x{F0}[\x{90}-\x{BF}][\x{80}-\x{BF}]{2}|",
                        "[\x{F1}-\x{F3}][\x{80}-\x{BF}]{3}|",
                        "\x{F4}[\x{80}-\x{8F}][\x{80}-\x{BF}]{2})|",
                        "(.)/s"
                    ];
                    $columnName = preg_replace(implode('', $regexParts), "$1", $column['name']);
                    $output .= 'Col' . ($index + 1) . '="' . $columnName . '"';
                    $output .= match ($column['type']) {
                        'integer' => " Integer\n",
                        'date' => " Date\n",
                        'datetime' => " DateTime\n",
                        default => " Text\n",
                    };
                }
                $output .= "MaxScanRows=0\n";
                $output .= "CharacterSet=ANSI\n";
                $output .= "DateTimeFormat=yyyy-MM-dd HH:nn:ss\n";
            }
            file_put_contents($schemaFilePath, $output);
        }
    }
}
