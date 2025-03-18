<?php

namespace GenericDatabase\Helpers\Zod;

use GenericDatabase\Helpers\Zod\Zod\ZodObject;
use GenericDatabase\Helpers\Zod\Zod\ZodToSchema;

class SchemaConverter
{
    /**
     * Converte um esquema Zod para JSON Schema
     * 
     * @param ZodObject $zodSchema O esquema Zod a ser convertido
     * @param string|null $outputPath Caminho para salvar o JSON Schema gerado
     * @return array O JSON Schema gerado
     */
    public function zodToJsonSchema(ZodObject $zodSchema, ?string $outputPath = null): array
    {
        // Usa nossa implementação personalizada para converter o esquema
        $zodToSchema = new ZodToSchema();
        $jsonSchema = $zodToSchema->generate($zodSchema);

        // Salva o esquema em um arquivo se especificado
        if ($outputPath) {
            file_put_contents(
                $outputPath,
                json_encode($jsonSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        return $jsonSchema;
    }
}
