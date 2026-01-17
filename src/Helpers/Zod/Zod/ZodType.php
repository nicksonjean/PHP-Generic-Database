<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Classe base para tipos Zod
 */
abstract class ZodType
{
    public $description = '';
    public $defaultValue = null;
    public $hasDefault = false;

    /**
     * Adiciona uma descrição ao tipo
     */
    public function describe(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Define um valor padrão
     */
    public function default($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->hasDefault = true;
        return $this;
    }

    /**
     * Valida um valor contra este tipo
     */
    abstract public function validate($value): array;

    /**
     * Converte este tipo para um formato de esquema JSON
     */
    abstract public function toJsonSchema(): array;
}
