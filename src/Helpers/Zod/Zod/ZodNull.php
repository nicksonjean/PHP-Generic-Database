<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo Null
 */
class ZodNull extends ZodType
{
    public function validate($value): array
    {
        $errors = [];

        // Tratar valor default
        if ($value === null && $this->hasDefault) {
            return ['value' => $this->defaultValue, 'errors' => []];
        }

        if ($value === null) {
            $value = true;
        } else {
            $value = false;
            $errors[] = ['message' => 'Valor deve ser um null', 'code' => 'invalid_type'];
        }

        return ['value' => $value, 'errors' => $errors];
    }

    public function toJsonSchema(): array
    {
        $schema = [
            'type' => 'null',
            'description' => $this->description
        ];

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}
