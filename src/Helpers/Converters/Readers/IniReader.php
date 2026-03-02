<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reads INI files from a directory tree and exposes them as decoded record arrays.
 *
 * The format produced by IniWriter is parsed manually (not via parse_ini_string)
 * to guarantee correct handling of string values escaped with addslashes and of
 * the options["key"]=value array syntax used for DSN option entries.
 *
 * Value decoding rules:
 *   - "string" (double-quoted)  → stripslashes → string
 *   - numeric                   → int or float
 *   - true / false              → bool
 *   - unquoted string           → string
 */
class IniReader extends BaseReader
{
    protected function load(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'ini') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $data = $this->parseIni($content);
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
     * Parse an INI string (IniWriter format) into an associative array.
     *
     * Handles:
     *   - key="value"                      → regular string field
     *   - key=3306                          → numeric field
     *   - key=true / key=false              → boolean field
     *   - options["OptionKey"]=value        → collected into options[0][...] array
     *
     * @param string $content Raw INI file content
     * @return array<string, mixed>
     */
    private function parseIni(string $content): array
    {
        $lines   = explode("\n", $content);
        $result  = [];
        $options = [];

        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '') {
                continue;
            }

            // options["OptionKey"]=value
            if (preg_match('/^options\["([^"]+)"\]=(.*)$/', $line, $m)) {
                $options[$m[1]] = $this->coerceIniValue($m[2]);
                continue;
            }

            // key=value
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)=(.*)$/', $line, $m)) {
                $result[$m[1]] = $this->coerceIniValue($m[2]);
            }
        }

        if (!empty($options)) {
            $result['options'] = [$options];
        }

        return $result;
    }

    public function getSourceFormat(): string
    {
        return 'ini';
    }
}
