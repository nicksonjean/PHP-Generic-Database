<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo numérico
 */
class ZodNumber extends ZodType
{
    public ?float $min = null;
    public ?float $max = null;
    public bool $isInteger = false;
    public ?bool $nullable = null;
    public ?string $minError = 'Número deve ser maior ou igual a {min}';
    public ?string $maxError = 'Número deve ser menor ou igual a {max}';
    public ?string $intError = 'Número deve ser um inteiro';
    public ?string $nullableError = 'String pode ser nullable';

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

    public function nullable(bool $nullable, ?string $message = null): self
    {
        $this->nullable = $nullable;
        if ($message !== null) {
            $this->nullableError = $message;
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

        // Validar nulável
        if ($this->nullable !== null && !is_bool($this->nullable)) {
            $message = str_replace('{nullable}', $this->nullable, $this->nullableError);
            $errors[] = ['message' => $message, 'code' => 'nullable'];
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

        if ($this->nullable !== null) {
            $schema['nullable'] = $this->nullable;
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}
