<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use GenericDatabase\Helpers\Zod\SchemaConverter;
use GenericDatabase\Helpers\Zod\SchemaParser;

class SchemaConverterStrategy extends SchemaConverter
{
    public SchemaParser $instance;

    public function __construct(SchemaParser $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Exporta o esquema de conexão MySQL para JSON Schema
     * 
     * @param string $outputPath Caminho do arquivo de saída
     * @return array O JSON Schema gerado
     */
    public function exportZodToJsonSchema(string $outputPath): array
    {
        $zodSchema = $this->instance->createSchema();
        return $this->zodToJsonSchema($zodSchema, $outputPath);
    }
}

// Exemplo de uso:
// SchemaConverter::exportMySQLConnectionSchema(__DIR__ . '/mysql-connection-jsonschema.json');