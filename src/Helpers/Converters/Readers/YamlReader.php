<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reads YAML files from a directory tree and exposes them as decoded record arrays.
 *
 * Parses the specific YAML subset produced by YamlWriter without any external YAML
 * library. Only the patterns actually emitted by the writer are handled:
 *
 *   key: value                   — scalar field
 *   options:                     — begins a block sequence
 *     - "QuotedKey": value       — first sequence item (two-space indent + hyphen)
 *       "QuotedKey": value       — subsequent items (four-space indent)
 *
 * Keys containing '::' are double-quoted in YAML (YamlWriter convention).
 * Keys without '::' are written unquoted.
 * Values containing '::' are double-quoted; numeric and boolean values are unquoted.
 */
class YamlReader extends BaseReader
{
    protected function load(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'yaml') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $data = $this->parseYaml($content);
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
     * Parse a YAML string (YamlWriter format) into an associative array.
     *
     * @param string $content Raw YAML file content
     * @return array<string, mixed>
     */
    private function parseYaml(string $content): array
    {
        $lines     = explode("\n", $content);
        $result    = [];
        $options   = [];
        $inOptions = false;

        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '') {
                continue;
            }

            if ($inOptions) {
                // First sequence item: "  - key: value"
                if (preg_match('/^  - (.+)$/', $line, $m)) {
                    $kv = $this->parseKeyValue($m[1]);
                    if ($kv !== null) {
                        $options[$kv[0]] = $this->coerceNeonValue($kv[1]);
                    }
                    continue;
                }
                // Subsequent sequence items: "    key: value"
                if (preg_match('/^    (.+)$/', $line, $m)) {
                    $kv = $this->parseKeyValue($m[1]);
                    if ($kv !== null) {
                        $options[$kv[0]] = $this->coerceNeonValue($kv[1]);
                    }
                    continue;
                }
                // Any non-indented line ends the options block
                $inOptions = false;
                if (!empty($options)) {
                    $result['options'] = [$options];
                }
            }

            // Start of options block
            if (preg_match('/^options:\s*$/', $line)) {
                $inOptions = true;
                $options   = [];
                continue;
            }

            // Regular top-level key: value
            if (preg_match('/^(\w+): (.*)$/', $line, $m)) {
                $result[$m[1]] = $this->coerceNeonValue($m[2]);
            }
        }

        // Flush options if file ends inside the block
        if ($inOptions && !empty($options)) {
            $result['options'] = [$options];
        }

        return $result;
    }

    public function getSourceFormat(): string
    {
        return 'yaml';
    }
}
