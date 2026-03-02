<?php

namespace GenericDatabase\Helpers\Converters\Writers;

/**
 * Converts a DSN record array to JSON format.
 *
 * Produces human-readable, pretty-printed JSON matching the canonical DSN file
 * format used as the source of truth. Unicode characters and forward slashes are
 * preserved unescaped for readability.
 */
class JsonWriter extends BaseWriter
{
    public function convert(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    public function getExtension(): string
    {
        return 'json';
    }

    public function getFormat(): string
    {
        return 'json';
    }
}
