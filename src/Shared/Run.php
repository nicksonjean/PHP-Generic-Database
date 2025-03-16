<?php

namespace GenericDatabase\Shared;

/**
 * This trait provides static method to dynamically call any function, method, or instance.
 *
 * Methods:
 * - `call(mixed $callable, mixed ...$args): mixed:` Calls a function, method, or instance dynamically.
 * - `callArray(mixed $callable, array $args = []): mixed:` Calls a function or method with an array of arguments.
 */
class Run
{
    /**
     * Static method to dynamically call any function, method, or instance.
     *
     * @param mixed $callable string or (global function) or array [class, method], [object, method] or closure.
     * @param mixed ...$args arguments to send to the callable
     * @return mixed return the result of the call
     */
    public static function call($callable, ...$args)
    {
        return call_user_func($callable, ...$args);
    }

    /**
     * Static method to call a function or method with an array of arguments.
     *
     * @param mixed $callable string or (global function) or array [class, method], [object, method] or closure.
     * @param array $args arguments to send to the callable
     * @return mixed return the result of the call
     */
    public static function callArray($callable, array $args = [])
    {
        return call_user_func_array($callable, $args);
    }
}
