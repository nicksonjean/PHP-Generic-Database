<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * CSV Exporter
 * Exports database tables to CSV files
 */
class CSVExporter
{
    private BaseExporter $exporter;
    private string $delimiter = ';';

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
     * Set delimiter for CSV files
     *
     * @param string $delimiter Delimiter character
     * @return self
     */
    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Export all tables to CSV files
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

            $filename = $outputPath . $table . '.csv';
            $handle = fopen($filename, 'w');

            if ($handle === false) {
                throw new Exception("Failed to create file: {$filename}");
            }

            // Write headers
            $headers = array_column($schema['columns'], 'name');
            $headers = array_map([$this->exporter, 'cleanString'], $headers);
            fputcsv($handle, $headers, $this->delimiter);

            // Write data
            foreach ($data as $row) {
                $values = [];
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    $values[] = $this->exporter->cleanString((string)$value);
                }
                fputcsv($handle, $values, $this->delimiter);
            }

            fclose($handle);
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

