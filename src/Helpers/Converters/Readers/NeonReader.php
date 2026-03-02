<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reads NEON files from a directory tree and exposes them as decoded record arrays.
 *
 * Parses the specific NEON subset produced by NeonWriter. No external NEON library
 * is required; the parser handles only the patterns actually emitted by the writer:
 *
 *   key: value          — scalar field (unquoted)
 *   options:            — begins a nested block
 *     Key: value        — two-space-indented option entry (keys unquoted, even for '::')
 *
 * Values containing '::' that were double-quoted by the writer are unquoted transparently.
 */
class NeonReader extends BaseReader
{
    protected function load(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'neon') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $data = $this->parseNeon($content);
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
     * Parse a NEON string (NeonWriter format) into an associative array.
     *
     * @param string $content Raw NEON file content
     * @return array<string, mixed>
     */
    private function parseNeon(string $content): array
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

            // Inside options block — two-space-indented entries
            if ($inOptions) {
                if (preg_match('/^  (.+)$/', $line, $m)) {
                    $kv = $this->parseKeyValue($m[1]);
                    if ($kv !== null) {
                        $options[$kv[0]] = $this->coerceNeonValue($kv[1]);
                    }
                    continue;
                }
                // Non-indented line ends the options block
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
        return 'neon';
    }
}
