<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reads CSV files from a directory tree and exposes them as decoded record arrays.
 *
 * Each CSV file produced by CsvWriter contains one header row and one data row.
 * All values are double-quoted in CSV; str_getcsv() handles unquoting automatically.
 * Numeric and boolean values are coerced back to their native PHP types.
 *
 * The 'options' field — serialized as a JSON string in the CSV cell — is decoded
 * back to the original nested array structure.
 */
class CsvReader extends BaseReader
{
    protected function load(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'csv') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $data = $this->parseCsv($content);
            if (empty($data)) {
                continue;
            }

            $relPath = substr(
                str_replace('\\', '/', $file->getPathname()),
                strlen($this->sourceDir) + 1
            );

            $this->records[$relPath] = $data;
        }
    }

    /**
     * Parse a two-row CSV string (header + data) into an associative array.
     *
     * @param string $content Raw CSV file content
     * @return array<string, mixed> Decoded record or empty array on failure
     */
    private function parseCsv(string $content): array
    {
        $lines = array_filter(explode("\n", trim($content)));
        if (count($lines) < 2) {
            return [];
        }

        $lines = array_values($lines);
        $keys  = str_getcsv($lines[0]);
        $vals  = str_getcsv($lines[1]);

        if (count($keys) !== count($vals)) {
            return [];
        }

        $data = array_combine($keys, $vals);

        foreach ($data as $k => $v) {
            if ($k === 'options') {
                // Options was JSON-encoded into a single CSV cell
                if (is_string($v) && str_starts_with(ltrim($v), '[')) {
                    $decoded = json_decode($v, true);
                    if (is_array($decoded)) {
                        $data[$k] = $decoded;
                        continue;
                    }
                }
                continue;
            }
            // Coerce scalar values back to numeric / bool types
            $data[$k] = $this->coerceValue($v);
        }

        return $data;
    }

    public function getSourceFormat(): string
    {
        return 'csv';
    }
}
