<?php

namespace GenericDatabase\Shared;

class Run
{
    /**
     * Método estático para chamar qualquer função, método ou instância dinamicamente.
     *
     * @param mixed $callable Pode ser uma string (função global), array [Classe, método], [Objeto, método] ou Closure.
     * @param mixed ...$args Argumentos a serem passados para o método/função.
     * @return mixed Retorna o resultado da chamada.
     */
    public static function call($callable, ...$args)
    {
        return call_user_func($callable, ...$args);
    }

    /**
     * Método estático para chamar uma função/método passando os argumentos como um array.
     *
     * @param mixed $callable Pode ser uma string (função global), array [Classe, método], [Objeto, método] ou Closure.
     * @param array $args Array de argumentos a serem passados.
     * @return mixed Retorna o resultado da chamada.
     */
    public static function callArray($callable, array $args = [])
    {
        return call_user_func_array($callable, $args);
    }
}
