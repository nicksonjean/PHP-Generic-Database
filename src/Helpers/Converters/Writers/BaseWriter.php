<?php

namespace GenericDatabase\Helpers\Converters\Writers;

use RuntimeException;

/**
 * Base abstract class for all converter format writers.
 *
 * Each concrete writer implements a specific output format (CSV, INI, JSON, NEON, XML, YAML).
 * The shared write() method handles idempotency: it compares the generated content
 * with the existing file and suppresses the write when they are identical.
 */
abstract class BaseWriter
{
    /**
     * Convert a decoded record array into the target format string.
     *
     * @param array<string, mixed> $data Decoded source record
     * @return string Serialized content in the target format
     */
    abstract public function convert(array $data): string;

    /**
     * Return the file extension for this format (without dot, e.g. 'csv').
     */
    abstract public function getExtension(): string;

    /**
     * Return the canonical format name used as result key (e.g. 'csv').
     */
    abstract public function getFormat(): string;

    /**
     * Optionally transform the relative target path before writing.
     *
     * Override in subclasses that require path-level adjustments
     * (e.g. CsvWriter applies the ODBC legacy filename rename).
     *
     * @param string $relPath Relative path with forward slashes
     * @return string Possibly modified relative path
     */
    public function transformRelPath(string $relPath): string
    {
        return $relPath;
    }

    /**
     * Write content to the target file, skipping unchanged content (idempotent).
     *
     * @param string $targetDir Absolute base directory for the target format
     * @param string $relPath   Relative path (forward slashes) to the target file
     * @param string $content   Serialized content to write
     * @return bool             True if the file was written (created or updated), false if unchanged
     * @throws RuntimeException On write failure
     */
    public function write(string $targetDir, string $relPath, string $content): bool
    {
        $targetPath = rtrim(str_replace('\\', '/', $targetDir), '/') . '/' . $relPath;
        $existing   = @file_get_contents($targetPath);

        if ($existing !== false && $existing === $content) {
            return false;
        }

        if (@file_put_contents($targetPath, $content) === false) {
            throw new RuntimeException("Failed to write: {$targetPath}");
        }

        return true;
    }
}
