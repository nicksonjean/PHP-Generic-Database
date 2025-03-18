<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo numérico
 */
class ZodNumber extends ZodType
{
    protected ?float $min = null;
    protected ?float $max = null;
    protected bool $isInteger = false;
    protected ?string $minError = 'Número deve ser maior ou igual a {min}';
    protected ?string $maxError = 'Número deve ser menor ou igual a {max}';
    protected ?string $intError = 'Número deve ser um inteiro';

    public function min(float $min, ?string $message = null): self
    {
        $this->min = $min;
        if ($message !== null) {
            $this->minError = $message;
        }
        return $this;
    }

    public function max(float $max, ?string $message = null): self
    {
        $this->max = $max;
        if ($message !== null) {
            $this->maxError = $message;
        }
        return $this;
    }

    public function int(?string $message = null): self
    {
        $this->isInteger = true;
        if ($message !== null) {
            $this->intError = $message;
        }
        return $this;
    }

    public function validate($value): array
    {
        $errors = [];

        // Tratar valor default
        if ($value === null && $this->hasDefault) {
            return ['value' => $this->defaultValue, 'errors' => []];
        }

        // Converter para número se possível
        if (is_string($value) && is_numeric($value)) {
            $value = $this->isInteger ? (int)$value : (float)$value;
        }

        if (!is_numeric($value)) {
            $errors[] = ['message' => 'Valor deve ser um número', 'code' => 'invalid_type'];
            return ['value' => $value, 'errors' => $errors];
        }

        // Validar se é inteiro
        if ($this->isInteger && !is_int($value) && (float)$value != (int)$value) {
            $errors[] = ['message' => $this->intError, 'code' => 'invalid_type'];
        }

        // Validar mínimo
        if ($this->min !== null && $value < $this->min) {
            $message = str_replace('{min}', $this->min, $this->minError);
            $errors[] = ['message' => $message, 'code' => 'too_small'];
        }

        // Validar máximo
        if ($this->max !== null && $value > $this->max) {
            $message = str_replace('{max}', $this->max, $this->maxError);
            $errors[] = ['message' => $message, 'code' => 'too_large'];
        }

        return ['value' => $value, 'errors' => $errors];
    }

    public function toJsonSchema(): array
    {
        $schema = [
            'type' => $this->isInteger ? 'integer' : 'number',
            'description' => $this->description
        ];

        if ($this->min !== null) {
            $schema['minimum'] = $this->min;
        }

        if ($this->max !== null) {
            $schema['maximum'] = $this->max;
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}
