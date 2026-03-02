<?php

namespace GenericDatabase\Helpers\Converters\Writers;

/**
 * Converts a DSN record array to CSV format.
 *
 * One header row is followed by one data row. The 'options' field — when it is
 * an array of key-value pairs — is serialized as a JSON string so that its
 * structure is preserved within a single CSV cell.
 *
 * Special case: within the ODBC subdirectory the standard odbc_* filename
 * prefix is intentionally stored as ocbc_* in CSV (legacy convention).
 * This is handled transparently by transformRelPath() without any configuration.
 */
class CsvWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        $keys = [];
        $row  = [];

        foreach ($data as $k => $v) {
            $keys[] = $k;
            if ($k === 'options' && is_array($v)) {
                $opt   = $v[0] ?? $v;
                $row[] = is_array($opt) ? json_encode([$opt]) : $v;
            } else {
                $row[] = $v;
            }
        }

        $escape = static function ($x): string {
            $s = (string) (is_bool($x) ? ($x ? 'true' : 'false') : $x);
            return '"' . str_replace('"', '""', $s) . '"';
        };

        return implode(',', $keys) . "\n" . implode(',', array_map($escape, $row)) . "\n";
    }

    public function getExtension(): string
    {
        return 'csv';
    }

    public function getFormat(): string
    {
        return 'csv';
    }

    /**
     * Rewrite odbc/odbc_* relative paths to odbc/ocbc_* for CSV only.
     * This mirrors the legacy naming convention already present in the DSN directory.
     */
    public function transformRelPath(string $relPath): string
    {
        if (str_contains($relPath, 'odbc/odbc_')) {
            return str_replace('odbc_', 'ocbc_', $relPath);
        }
        return $relPath;
    }
}
