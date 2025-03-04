<?php

namespace GenericDatabase\Shared;

/**
 * Static method to dynamically call any function, method, or instance.
 *
 * @param mixed $callable Can be a string (global function), array [Class, method], [Object, method], or Closure.
 * @param mixed ...$args Arguments to be passed to the method/function.
 * @return mixed Returns the result of the call.
 */
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
