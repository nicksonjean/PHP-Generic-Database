<?php

namespace GenericDatabase\Shared;

use ReflectionException;
use GenericDatabase\Helpers\Exceptions;

/**
 * This trait uses the Getter and Setter traits to facilitate dynamic property access. The `__call` method handles instance method calls,
 * while `__callStatic` manages static method calls. Both methods interpret method names starting with 'set' or 'get' to perform
 * corresponding actions on properties.
 *
 * Methods:
 * - `__get(string $name): mixed:` Retrieves the value of a property if it exists, or returns null if the property is inaccessible or non-existent.
 * - `__set(string $name, mixed $value): void:` Magic method to set the value of inaccessible or non-existing properties.
 * - `__call(string $name, array $arguments): mixed:` Handles dynamic instance method calls.
 * - `__callStatic(string $name, array $arguments): mixed:` Handles dynamic static method calls.
 *
 * Fields:
 * - `$property`: Stores properties for dynamic property access.
 */
trait Caller
{
    use Getter;
    use Setter;

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return mixed
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function __call(string $name, array $arguments): mixed
    {
        $method = substr($name, 0, 3);
        $field = mb_strtolower(substr($name, 3));
        if ($method == 'set') {
            $this->__set($field, ...$arguments);
            return $this;
        } elseif ($method == 'get') {
            return $this->__get($field);
        }
        return null;
    }

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return mixed
     * @throws Exceptions
     * @throws ReflectionException
     * @noinspection PhpExpressionResultUnusedInspection
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        if (method_exists(static::class, 'call')) {
            $instance = new static();
            $instance->call($name, $arguments); // @phpstan-ignore-line
            return $instance;
        }
        return null;
    }
}

