<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RuntimeException;

/**
 * Base abstract class for all converter source readers.
 *
 * Provides common scaffolding and shared type-coercion helpers used by all
 * format-specific reader implementations. The constructor validates the source
 * directory and delegates file loading to the concrete subclass via load().
 */
abstract class BaseReader
{
    protected string $sourceDir;

    /**
     * Relative path (with source extension, forward slashes) => decoded record array.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $records = [];

    /**
     * @param string $sourceDir Absolute path to the directory containing source files
     * @throws RuntimeException When the source directory does not exist
     */
    public function __construct(string $sourceDir)
    {
        $this->sourceDir = rtrim(str_replace('\\', '/', $sourceDir), '/');

        if (!is_dir($this->sourceDir)) {
            throw new RuntimeException("Source directory not found: {$this->sourceDir}");
        }

        $this->load();
    }

    /**
     * Recursively load all source files and decode them into $this->records.
     *
     * Keys must be relative paths with forward slashes including the source extension.
     */
    abstract protected function load(): void;

    /**
     * Return the canonical name of the source format (e.g. 'json', 'csv').
     */
    abstract public function getSourceFormat(): string;

    /**
     * Return all loaded records.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Return the absolute source directory path.
     */
    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    // -----------------------------------------------------------------------
    // Shared type-coercion helpers
    // -----------------------------------------------------------------------

    /**
     * Coerce a plain (unquoted) string value to the most appropriate PHP type.
     *
     * Rules (in order):
     *   - numeric   → int or float
     *   - 'true'    → true  (bool)
     *   - 'false'   → false (bool)
     *   - otherwise → string (unchanged)
     */
    protected function coerceValue(string $value): int|float|bool|string
    {
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        return $value;
    }

    /**
     * Coerce a NEON/YAML-style value.
     *
     * Strips surrounding double quotes (used for values containing '::') then
     * delegates to coerceValue(). No addslashes escaping is present in NEON/YAML.
     */
    protected function coerceNeonValue(string $value): int|float|bool|string
    {
        $v = trim($value);
        if (strlen($v) >= 2 && $v[0] === '"' && $v[-1] === '"') {
            return substr($v, 1, -1);
        }
        return $this->coerceValue($v);
    }

    /**
     * Coerce an INI-style value.
     *
     * Strips surrounding double quotes and applies stripslashes (IniWriter uses
     * addslashes when quoting string values), then delegates to coerceValue().
     */
    protected function coerceIniValue(string $value): int|float|bool|string
    {
        $v = trim($value);
        if (strlen($v) >= 2 && $v[0] === '"' && $v[-1] === '"') {
            return stripslashes(substr($v, 1, -1));
        }
        return $this->coerceValue($v);
    }

    /**
     * Parse a single "key: value" line where the key may optionally be double-quoted.
     *
     * Handles both NEON-style (unquoted keys) and YAML-style (double-quoted keys
     * for identifiers containing '::'). Returns [key, rawValueString] or null when
     * the line does not match the expected pattern.
     *
     * The key pattern supports '::' within identifiers (e.g. MySQL::ATTR_PERSISTENT)
     * by only matching ': ' (colon + space) as the separator, not bare colons.
     *
     * @param string $line A single trimmed content line (without leading indentation)
     * @return array{0: string, 1: string}|null [key, rawValue] or null on no match
     */
    protected function parseKeyValue(string $line): ?array
    {
        // Double-quoted key — used by YAML for keys containing '::'
        if (preg_match('/^"([^"]+)": (.*)$/', $line, $m)) {
            return [$m[1], $m[2]];
        }
        // Unquoted key — may contain '::' but separator is always ': ' (colon+space)
        if (preg_match('/^([^:]+(?:::[^:]+)*): (.*)$/', $line, $m)) {
            return [trim($m[1]), $m[2]];
        }
        return null;
    }
}
