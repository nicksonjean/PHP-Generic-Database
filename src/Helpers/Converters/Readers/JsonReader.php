<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reads JSON files from a directory tree and exposes them as decoded record arrays.
 *
 * Only files with a valid JSON array payload are included. Files that fail to
 * decode (invalid JSON, non-array root) are silently skipped.
 *
 * Record keys are relative paths with forward slashes, retaining the original
 * directory structure under $sourceDir (e.g. "odbc/odbc_mysql.json").
 */
class JsonReader extends BaseReader
{
    protected function load(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'json') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                continue;
            }

            $relPath = substr(
                str_replace('\\', '/', $file->getPathname()),
                strlen($this->sourceDir) + 1
            );

            $this->records[$relPath] = $data;
        }
    }

    public function getSourceFormat(): string
    {
        return 'json';
    }
}
