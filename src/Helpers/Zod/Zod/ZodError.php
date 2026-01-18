<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Classe de erro para validações
 */
class ZodError extends \Exception
{
    public array $errors = [];

    public function __construct(array $errors, int $code = 0, \Throwable $previous = null)
    {
        $this->errors = $errors;
        $message = "Erro de validação: " . json_encode($errors, JSON_UNESCAPED_UNICODE);
        parent::__construct($message, $code, $previous);
    }
}

