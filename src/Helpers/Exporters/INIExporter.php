<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * INI Exporter
 * Exports database tables to INI files
 */
class INIExporter
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
     * Export all tables to INI files
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

            $filename = $outputPath . $table . '.ini';
            $iniContent = $this->generateIniContent($data, $schema);

            if (file_put_contents($filename, $iniContent) === false) {
                throw new Exception("Failed to save INI file: {$filename}");
            }

            $exportedFiles[] = $filename;
        }

        return $exportedFiles;
    }

    /**
     * Generate INI content from table data
     *
     * @param array $data Table data rows
     * @param array $schema Table schema
     * @return string INI formatted content
     */
    private function generateIniContent(array $data, array $schema): string
    {
        $lines = [];
        
        // Add header comment
        $lines[] = "; INI file generated from database table";
        $lines[] = "; Table: " . ($schema['name'] ?? 'unknown');
        $lines[] = "";

        // If no data, create empty sections for each column
        if (empty($data)) {
            foreach ($schema['columns'] as $column) {
                $columnName = $this->exporter->cleanString($column['name']);
                $lines[] = "[{$columnName}]";
                $lines[] = "value = ";
                $lines[] = "";
            }
            return implode("\n", $lines);
        }

        // Each row becomes a section
        foreach ($data as $rowIndex => $row) {
            $sectionName = 'row_' . ($rowIndex + 1);
            $lines[] = "[{$sectionName}]";
            
            foreach ($row as $key => $value) {
                $cleanKey = $this->exporter->cleanString($key);
                $cleanValue = $this->escapeIniValue($value);
                $lines[] = "{$cleanKey} = {$cleanValue}";
            }
            
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    /**
     * Escape INI value
     *
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    private function escapeIniValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        $value = (string) $value;
        
        // Escape special characters
        if (str_contains($value, '"') || str_contains($value, ';') || str_contains($value, '=') || 
            str_contains($value, "\n") || str_contains($value, "\r") || 
            str_starts_with($value, ' ') || str_ends_with($value, ' ')) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
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
