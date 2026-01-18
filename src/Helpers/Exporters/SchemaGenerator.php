<?php

namespace GenericDatabase\Helpers\Exporters;

use Exception;

/**
 * Schema Generator
 * Generates Schema.ini files for different export formats
 */
class SchemaGenerator
{
    private string $outputPath;
    private array $tables;
    private array $tableSchemas;
    private string $format;

    /**
     * Constructor
     *
     * @param string $outputPath Path where Schema.ini will be saved
     * @param array $tables Array of table names
     * @param array $tableSchemas Array of table schemas
     * @param string $format Format type (csv, json, xml, yaml)
     */
    public function __construct(string $outputPath, array $tables, array $tableSchemas, string $format)
    {
        $this->outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->tables = $tables;
        $this->tableSchemas = $tableSchemas;
        $this->format = strtolower($format);
    }

    /**
     * Generate Schema.ini file
     *
     * @param BaseExporter $exporter Exporter instance to get foreign keys
     * @return string Path to generated Schema.ini file
     * @throws Exception
     */
    public function generate(BaseExporter $exporter): string
    {
        $filename = $this->outputPath . 'Schema.ini';
        $content = $this->buildSchemaContent($exporter);

        if (file_put_contents($filename, $content) === false) {
            throw new Exception("Failed to create Schema.ini file: {$filename}");
        }

        return $filename;
    }

    /**
     * Build Schema.ini content
     *
     * @param BaseExporter $exporter Exporter instance
     * @return string Schema.ini content
     */
    private function buildSchemaContent(BaseExporter $exporter): string
    {
        $lines = [];

        // Add header comment based on format
        switch ($this->format) {
            case 'csv':
                $lines[] = '# Schema.ini para arquivos CSV';
                $lines[] = '# Este arquivo define a estrutura das tabelas e seus relacionamentos';
                $lines[] = '# Compatível com bibliotecas que leem CSV como banco de dados (ex: ODBC-Text Connector)';
                break;
            case 'json':
                $lines[] = '# Schema.ini para arquivos JSON';
                $lines[] = '# Este arquivo define a estrutura das tabelas e seus relacionamentos';
                $lines[] = '# Compatível com bibliotecas que leem JSON como banco de dados (ex: php-jsondb)';
                break;
            case 'xml':
                $lines[] = '# Schema.ini para arquivos XML';
                $lines[] = '# Este arquivo define a estrutura das tabelas e seus relacionamentos';
                $lines[] = '# Compatível com bibliotecas que leem XML como banco de dados';
                break;
            case 'yaml':
                $lines[] = '# Schema.ini para arquivos YAML';
                $lines[] = '# Este arquivo define a estrutura das tabelas e seus relacionamentos';
                $lines[] = '# Compatível com bibliotecas que leem YAML como banco de dados';
                break;
        }

        $lines[] = '';

        // Generate schema for each table
        foreach ($this->tables as $table) {
            $schema = $this->tableSchemas[$table];
            $extension = $this->format === 'csv' ? 'csv' : $this->format;
            $filename = "[{$table}.{$extension}]";
            $lines[] = $filename;

            // Format specific settings
            switch ($this->format) {
                case 'csv':
                    $lines[] = "Format=Delimited({$this->getDelimiter()})";
                    $lines[] = "TableName={$table}";
                    if ($schema['primaryKey']) {
                        $lines[] = "PrimaryKey={$schema['primaryKey']}";
                    }
                    $lines[] = "ColNameHeader=True";
                    break;
                case 'json':
                    $lines[] = "Format=JSON";
                    $lines[] = "TableName={$table}";
                    if ($schema['primaryKey']) {
                        $lines[] = "PrimaryKey={$schema['primaryKey']}";
                    }
                    break;
                case 'xml':
                    $lines[] = "Format=XML";
                    $lines[] = "TableName={$table}";
                    if ($schema['primaryKey']) {
                        $lines[] = "PrimaryKey={$schema['primaryKey']}";
                    }
                    $lines[] = "RootElement=root";
                    $lines[] = "ItemElement=item";
                    break;
                case 'yaml':
                    $lines[] = "Format=YAML";
                    $lines[] = "TableName={$table}";
                    if ($schema['primaryKey']) {
                        $lines[] = "PrimaryKey={$schema['primaryKey']}";
                    }
                    break;
            }

            // Column definitions
            $colIndex = 1;
            foreach ($schema['columns'] as $column) {
                $type = $column['type'];
                $name = $column['name'];
                $lines[] = "Col{$colIndex}=\"{$name}\" {$type}";
                $colIndex++;
            }

            // Foreign keys for all formats
            $foreignKeys = $exporter->getForeignKeys($table);
            if (!empty($foreignKeys)) {
                $fkStrings = [];
                foreach ($foreignKeys as $fk) {
                    $fkStrings[] = "{$fk['from']}->{$fk['table']}.{$extension}({$fk['to']})";
                }
                if (count($fkStrings) > 0) {
                    $lines[] = "ForeignKeys=" . implode(',', $fkStrings);
                }
            }

            // Common settings
            if ($this->format === 'csv') {
                $lines[] = "MaxScanRows=0";
                $lines[] = "CharacterSet=ANSI";
            } else {
                $lines[] = "CharacterSet=UTF-8";
            }
            $lines[] = "DateTimeFormat=yyyy-MM-dd HH:nn:ss";
            $lines[] = '';
        }

        // Add relationships comment for all formats
        $lines[] = '# Relacionamentos entre tabelas:';
        foreach ($this->tables as $table) {
            $foreignKeys = $exporter->getForeignKeys($table);
            foreach ($foreignKeys as $fk) {
                $lines[] = "# {$table} -> {$fk['table']} ({$fk['from']})";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get delimiter for CSV format
     *
     * @return string Delimiter
     */
    private function getDelimiter(): string
    {
        return ';';
    }
}

