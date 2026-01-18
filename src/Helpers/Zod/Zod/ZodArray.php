<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Tipo array
 */
class ZodArray extends ZodType
{
    public ?ZodType $itemType = null;
    public ?int $minLength = null;
    public ?int $maxLength = null;
    public ?string $minLengthError = 'Array deve ter pelo menos {min} itens';
    public ?string $maxLengthError = 'Array não pode ter mais que {max} itens';

    public function of(ZodType $itemType): self
    {
        $this->itemType = $itemType;
        return $this;
    }

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

    public function validate($value): array
    {
        $errors = [];

        // Tratar valor default
        if ($value === null && $this->hasDefault) {
            return ['value' => $this->defaultValue, 'errors' => []];
        }

        // Verificar se é array
        if (!is_array($value)) {
            $errors[] = ['message' => 'Valor deve ser um array', 'code' => 'invalid_type'];
            return ['value' => $value, 'errors' => $errors];
        }

        // Validar comprimento mínimo
        if ($this->minLength !== null && count($value) < $this->minLength) {
            $message = str_replace('{min}', $this->minLength, $this->minLengthError);
            $errors[] = ['message' => $message, 'code' => 'too_small'];
        }

        // Validar comprimento máximo
        if ($this->maxLength !== null && count($value) > $this->maxLength) {
            $message = str_replace('{max}', $this->maxLength, $this->maxLengthError);
            $errors[] = ['message' => $message, 'code' => 'too_large'];
        }

        // Validar itens do array
        if ($this->itemType !== null) {
            $validatedItems = [];
            foreach ($value as $index => $item) {
                $result = $this->itemType->validate($item);
                $validatedItems[$index] = $result['value'];

                foreach ($result['errors'] as $error) {
                    $error['path'] = [$index, ...(isset($error['path']) ? $error['path'] : [])];
                    $errors[] = $error;
                }
            }
            $value = $validatedItems;
        }

        return ['value' => $value, 'errors' => $errors];
    }

    public function toJsonSchema(): array
    {
        $schema = [
            'type' => 'array',
            'description' => $this->description
        ];

        if ($this->itemType !== null) {
            $schema['items'] = $this->itemType->toJsonSchema();
        }

        if ($this->minLength !== null) {
            $schema['minItems'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $schema['maxItems'] = $this->maxLength;
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->defaultValue;
        }

        return $schema;
    }
}

