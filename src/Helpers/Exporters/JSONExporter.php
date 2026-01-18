<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * JSON Exporter
 * Exports database tables to JSON files
 */
class JSONExporter
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
     * Export all tables to JSON files
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

            // Clean headers
            $cleanedData = [];
            foreach ($data as $row) {
                $cleanedRow = [];
                foreach ($row as $key => $value) {
                    $cleanedKey = $this->exporter->cleanString($key);
                    $cleanedRow[$cleanedKey] = $value;
                }
                $cleanedData[] = $cleanedRow;
            }

            $filename = $outputPath . $table . '.json';
            $jsonContent = json_encode($cleanedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (file_put_contents($filename, $jsonContent) === false) {
                throw new Exception("Failed to save JSON file: {$filename}");
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
