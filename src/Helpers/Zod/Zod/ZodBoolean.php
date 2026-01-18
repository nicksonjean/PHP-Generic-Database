<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo booleano
 */
class ZodBoolean extends ZodType
{
    public function validate($value): array
    {
        $errors = [];

        // Tratar valor default
        if ($value === null && $this->hasDefault) {
            return ['value' => $this->defaultValue, 'errors' => []];
        }

        // Converter para booleano se possível
        if (is_string($value)) {
            $value = strtolower($value);
            if ($value === 'true' || $value === '1') {
                $value = true;
            } elseif ($value === 'false' || $value === '0') {
                $value = false;
            }
        } elseif (is_numeric($value)) {
            $value = (bool)$value;
        }

        if (!is_bool($value)) {
            $errors[] = ['message' => 'Valor deve ser um booleano', 'code' => 'invalid_type'];
        }

        return ['value' => $value, 'errors' => $errors];
    }

    public function toJsonSchema(): array
    {
        $schema = [
            'type' => 'boolean',
            'description' => $this->description
        ];

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}

