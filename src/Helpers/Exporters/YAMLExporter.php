<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * YAML Exporter
 * Exports database tables to YAML files
 */
class YAMLExporter
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
     * Export all tables to YAML files
     *
     * @return array Array of exported file paths
     * @throws Exception
     */
    public function export(): array
    {
        if (!function_exists('yaml_emit')) {
            throw new Exception("YAML extension is not available. Please install php-yaml extension.");
        }

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

            $filename = $outputPath . $table . '.yaml';
            $yamlContent = yaml_emit($cleanedData, YAML_UTF8_ENCODING);

            if (file_put_contents($filename, $yamlContent) === false) {
                throw new Exception("Failed to save YAML file: {$filename}");
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
