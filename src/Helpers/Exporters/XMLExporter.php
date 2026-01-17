<?php

namespace GenericDatabase\Helpers\Exporters;

use DOMDocument;
use Exception;

/**
 * XML Exporter
 * Exports database tables to XML files
 */
class XMLExporter
{
    private BaseExporter $exporter;

    /**
     * Constructor
     *
     * @param BaseExporter $exporter Engine-specific exporter instance
     */
    public function __construct(BaseExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Export all tables to XML files
     *
     * @return array Array of exported file paths
     * @throws Exception
     */
    public function export(): array
    {
        $exportedFiles = [];
        $tables = $this->exporter->getTables();
        $tableSchemas = $this->exporter->getTableSchemas();
        $outputPath = $this->exporter->getOutputPath();

        foreach ($tables as $table) {
            $data = $this->exporter->getTableData($table);
            $schema = $tableSchemas[$table];

            if (empty($data) && empty($schema['columns'])) {
                continue;
            }

            $filename = $outputPath . $table . '.xml';
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;

            $root = $xml->createElement('root');
            $xml->appendChild($root);

            // Create mapping of sanitized headers
            $sanitizedHeaders = [];
            $headerCounts = [];
            foreach ($schema['columns'] as $column) {
                $sanitized = $this->exporter->sanitizeXmlName($column['name']);
                if (isset($headerCounts[$sanitized])) {
                    $headerCounts[$sanitized]++;
                    $sanitized = $sanitized . '_' . $headerCounts[$sanitized];
                } else {
                    $headerCounts[$sanitized] = 0;
                }
                $sanitizedHeaders[$column['name']] = $sanitized;
            }

            // Add data rows
            foreach ($data as $row) {
                $item = $xml->createElement('item');
                foreach ($row as $key => $value) {
                    $sanitizedKey = $sanitizedHeaders[$key] ?? $this->exporter->sanitizeXmlName($key);
                    $element = $xml->createElement($sanitizedKey);
                    $element->appendChild($xml->createTextNode($value ?? ''));
                    $item->appendChild($element);
                }
                $root->appendChild($item);
            }

            if ($xml->save($filename) === false) {
                throw new Exception("Failed to save XML file: {$filename}");
            }

            $exportedFiles[] = $filename;
        }

        return $exportedFiles;
    }

    /**
     * Get exporter instance
     *
     * @return BaseExporter
     */
    public function getExporter(): BaseExporter
    {
        return $this->exporter;
    }
}
