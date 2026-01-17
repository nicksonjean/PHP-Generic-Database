<?php

namespace GenericDatabase\Helpers\Parsers;

/**
 * The `GenericDatabase\Helpers\Parsers\CSV` class provides validation and parsing functionalities for CSV files.
 *
 * Example Usage:
 * <code>
 * // Validate a CSV file
 * $csv = 'example.csv';
 * $isValidCSV = \GenericDatabase\Helpers\Parsers\CSV::isValidCSV($csv);
 * </code>
 *
 * <code>
 * // Parse a CSV file
 * $parsedCSV = \GenericDatabase\Helpers\Parsers\CSV::parseCSV('example.csv');
 * </code>
 *
 * Main functionalities:
 * - The `isValidCSV` method checks if a CSV file is valid.
 * - The `parseCSV` method parses CSV from file.
 * - The `emitCSV` method converts array data to CSV string.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage CSV
 */
class CSV
{
    private static string $delimiter = ',';
    private static string $enclosure = '"';
    private static string $escape = '\\';

    /**
     * Set delimiter for CSV parsing/output
     *
     * @param string $delimiter
     * @return void
     */
    public static function setDelimiter(string $delimiter): void
    {
        self::$delimiter = $delimiter;
    }

    /**
     * Get delimiter for CSV parsing/output
     *
     * @return string
     */
    public static function getDelimiter(): string
    {
        return self::$delimiter;
    }

    /**
     * Set enclosure for CSV parsing/output
     *
     * @param string $enclosure
     * @return void
     */
    public static function setEnclosure(string $enclosure): void
    {
        self::$enclosure = $enclosure;
    }

    /**
     * Get enclosure for CSV parsing/output
     *
     * @return string
     */
    public static function getEnclosure(): string
    {
        return self::$enclosure;
    }

    /**
     * Set escape character for CSV parsing/output
     *
     * @param string $escape
     * @return void
     */
    public static function setEscape(string $escape): void
    {
        self::$escape = $escape;
    }

    /**
     * Get escape character for CSV parsing/output
     *
     * @return string
     */
    public static function getEscape(): string
    {
        return self::$escape;
    }

    /**
     * Check if CSV file is valid
     *
     * @param mixed $csv Argument to be tested
     * @return bool
     */
    public static function isValidCSV(mixed $csv): bool
    {
        if (!is_string($csv)) {
            return false;
        }

        if (!str_ends_with($csv, '.csv')) {
            return false;
        }

        if (!file_exists($csv)) {
            return false;
        }

        $handle = @fopen($csv, 'r');
        if ($handle === false) {
            return false;
        }

        $firstLine = fgetcsv($handle, 0, self::$delimiter, self::$enclosure, self::$escape);
        fclose($handle);

        return $firstLine !== false && is_array($firstLine);
    }

    /**
     * Parse a CSV file
     *
     * @param string $filePath Path to the CSV file
     * @return array
     */
    public static function parseCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $result = [];
        $headers = null;
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, self::$delimiter, self::$enclosure, self::$escape)) !== false) {
            if ($lineNumber === 0) {
                $headers = $row;
            } else {
                if ($headers !== null) {
                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        $value = $row[$index] ?? null;
                        // Try to decode JSON values (for options array)
                        if ($value !== null && is_string($value)) {
                            $decoded = json_decode($value, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $value = $decoded;
                            }
                        }
                        $rowData[$header] = $value;
                    }
                    $result = $rowData;
                    break; // For DSN files, we only need the first data row
                } else {
                    $result[] = $row;
                }
            }
            $lineNumber++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * Parse a CSV file and return all rows
     *
     * @param string $filePath Path to the CSV file
     * @param bool $hasHeader Whether the first row is a header
     * @return array
     */
    public static function parseCSVAll(string $filePath, bool $hasHeader = true): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $result = [];
        $headers = null;
        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, self::$delimiter, self::$enclosure, self::$escape)) !== false) {
            if ($lineNumber === 0 && $hasHeader) {
                $headers = $row;
            } else {
                if ($headers !== null) {
                    $result[] = array_combine($headers, array_pad($row, count($headers), null));
                } else {
                    $result[] = $row;
                }
            }
            $lineNumber++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * Emit/convert array data to CSV string
     *
     * @param array $data Data to convert to CSV
     * @param bool $includeHeader Whether to include header row
     * @return string
     */
    public static function emitCSV(array $data, bool $includeHeader = true): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://memory', 'r+');
        if ($output === false) {
            return '';
        }

        $firstRow = reset($data);
        $headers = is_array($firstRow) ? array_keys($firstRow) : [];

        if ($includeHeader && !empty($headers)) {
            fputcsv($output, $headers, self::$delimiter, self::$enclosure, self::$escape);
        }

        foreach ($data as $row) {
            if (is_array($row)) {
                fputcsv($output, array_values($row), self::$delimiter, self::$enclosure, self::$escape);
            }
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '';
    }
}
