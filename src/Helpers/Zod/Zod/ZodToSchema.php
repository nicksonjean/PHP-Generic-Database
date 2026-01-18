<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Classe para converter esquema Zod para JSON Schema
 */
class ZodToSchema
{
    public function generate(ZodObject $schema): array
    {
        return $schema->toJsonSchema();
    }
}

