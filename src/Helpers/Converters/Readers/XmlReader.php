<?php

namespace GenericDatabase\Helpers\Converters\Readers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Reads XML files from a directory tree and exposes them as decoded record arrays.
 *
 * Parses the specific XML structure produced by XmlWriter using SimpleXML.
 * Both root element variants are supported:
 *   - <root>   for network-based DSNs (those containing a 'host' element)
 *   - <config> for flat-file DSNs (CSV, INI, JSON, NEON, XML, YAML path references)
 *
 * The <options> block is decoded back into a nested array matching the original
 * JSON structure: options[0][OptionKey] = coercedValue.
 */
class XmlReader extends BaseReader
{
    protected function load(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'xml') {
                continue;
            }

            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $data = $this->parseXml($content);
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
     * Parse an XML string (XmlWriter format) into an associative array.
     *
     * @param string $content Raw XML file content
     * @return array<string, mixed>
     */
    private function parseXml(string $content): array
    {
        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            return [];
        }

        $data = [];

        foreach ($xml->children() as $key => $node) {
            if ($key === 'options') {
                $options = [];
                foreach ($node->option as $option) {
                    $name           = (string) $option['name'];
                    $options[$name] = $this->coerceValue((string) $option);
                }
                if (!empty($options)) {
                    $data['options'] = [$options];
                }
            } else {
                $data[$key] = $this->coerceValue((string) $node);
            }
        }

        return $data;
    }

    public function getSourceFormat(): string
    {
        return 'xml';
    }
}
