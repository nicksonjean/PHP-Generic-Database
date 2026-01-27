<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;
use Nette\Neon\Neon;

/**
 * NEON Exporter
 * Exports database tables to NEON files using Nette/Neon
 */
class NEONExporter
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
     * Export all tables to NEON files
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

            $filename = $outputPath . $table . '.neon';
            
            try {
                // Encode data to NEON format (default format is already readable)
                $neonContent = Neon::encode($cleanedData);
            } catch (Exception $e) {
                throw new Exception("Failed to encode NEON content for table {$table}: " . $e->getMessage());
            }

            if (file_put_contents($filename, $neonContent) === false) {
                throw new Exception("Failed to save NEON file: {$filename}");
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
