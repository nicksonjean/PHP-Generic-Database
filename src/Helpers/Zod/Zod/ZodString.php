<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo de string
 */
class ZodString extends ZodType
{
    public ?int $minLength = null;
    public ?int $maxLength = null;
    public ?string $pattern = null;
    public ?bool $nullable = null;
    public ?string $minLengthError = 'String deve ter pelo menos {min} caracteres';
    public ?string $maxLengthError = 'String não pode ter mais que {max} caracteres';
    public ?string $patternError = 'String deve corresponder ao padrão especificado';
    public ?string $nullableError = 'String pode ser nullable';

    public function min(int $minLength, ?string $message = null): self
    {
        $this->minLength = $minLength;
        if ($message !== null) {
            $this->minLengthError = $message;
        }
        return $this;
    }

    public function max(int $maxLength, ?string $message = null): self
    {
        $this->maxLength = $maxLength;
        if ($message !== null) {
            $this->maxLengthError = $message;
        }
        return $this;
    }

    public function regex(string $pattern, ?string $message = null): self
    {
        $this->pattern = $pattern;
        if ($message !== null) {
            $this->patternError = $message;
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

    public function email(?string $message = null): self
    {
        $this->pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        if ($message !== null) {
            $this->patternError = $message;
        } else {
            $this->patternError = 'Email inválido';
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

        // Converter para string se possível
        if (!is_string($value)) {
            if (is_numeric($value) || is_bool($value)) {
                $value = (string)$value;
            } else {
                $errors[] = ['message' => 'Valor deve ser uma string', 'code' => 'invalid_type'];
                return ['value' => $value, 'errors' => $errors];
            }
        }

        // Validar nulável
        if ($this->nullable !== null && !is_bool($this->nullable)) {
            $message = str_replace('{nullable}', $this->nullable, $this->nullableError);
            $errors[] = ['message' => $message, 'code' => 'nullable'];
        }

        // Validar comprimento mínimo
        if ($this->minLength !== null && mb_strlen($value) < $this->minLength) {
            $message = str_replace('{min}', $this->minLength, $this->minLengthError);
            $errors[] = ['message' => $message, 'code' => 'too_small'];
        }

        // Validar comprimento máximo
        if ($this->maxLength !== null && mb_strlen($value) > $this->maxLength) {
            $message = str_replace('{max}', $this->maxLength, $this->maxLengthError);
            $errors[] = ['message' => $message, 'code' => 'too_large'];
        }

        // Validar padrão regex
        if ($this->pattern !== null && !preg_match($this->pattern, $value)) {
            $errors[] = ['message' => $this->patternError, 'code' => 'invalid_pattern'];
        }

        return ['value' => $value, 'errors' => $errors];
    }

    public function toJsonSchema(): array
    {
        $schema = [
            'type' => 'string',
            'description' => $this->description
        ];

        if ($this->minLength !== null) {
            $schema['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $schema['maxLength'] = $this->maxLength;
        }

        if ($this->nullable !== null) {
            $schema['nullable'] = $this->nullable;
        }

        if ($this->pattern !== null) {
            $schema['pattern'] = $this->pattern;
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}
