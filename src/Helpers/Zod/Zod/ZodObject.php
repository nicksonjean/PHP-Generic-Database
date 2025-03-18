<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo objeto
 */
class ZodObject extends ZodType
{
    protected array $shape = [];

    public function __construct(array $shape)
    {
        $this->shape = $shape;
    }

    public function validate($value): array
    {
        $errors = [];
        $validated = [];

        // Tratar valor default
        if ($value === null && $this->hasDefault) {
            return ['value' => $this->defaultValue, 'errors' => []];
        }

        // Verificar se é array
        if (!is_array($value) && !is_object($value)) {
            $errors[] = ['message' => 'Valor deve ser um objeto', 'code' => 'invalid_type'];
            return ['value' => $value, 'errors' => $errors];
        }

        // Converter para array associativo se for objeto
        if (is_object($value)) {
            $value = (array)$value;
        }

        // Validar propriedades
        foreach ($this->shape as $key => $type) {
            if (!array_key_exists($key, $value)) {
                // Se a propriedade não existir, mas tiver valor padrão, use-o
                if ($type->hasDefault) {
                    $validated[$key] = $type->defaultValue;
                    continue;
                }

                $errors[] = [
                    'path' => [$key],
                    'message' => "Propriedade '$key' é obrigatória",
                    'code' => 'required'
                ];
                continue;
            }

            $result = $type->validate($value[$key]);
            $validated[$key] = $result['value'];

            foreach ($result['errors'] as $error) {
                $error['path'] = [$key, ...(isset($error['path']) ? $error['path'] : [])];
                $errors[] = $error;
            }
        }

        return ['value' => $validated, 'errors' => $errors];
    }

    public function parse($value): array
    {
        $result = $this->validate($value);

        if (!empty($result['errors'])) {
            throw new ZodError($result['errors']);
        }

        return $result['value'];
    }

    public function toJsonSchema(): array
    {
        $schema = [
            'type' => 'object',
            'description' => $this->description,
            'properties' => []
        ];

        $required = [];

        foreach ($this->shape as $key => $type) {
            $schema['properties'][$key] = $type->toJsonSchema();

            // Se não tem default, é obrigatório
            if (!$type->hasDefault) {
                $required[] = $key;
            }
        }

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}
