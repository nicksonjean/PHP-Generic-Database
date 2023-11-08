<?php

namespace GenericDatabase\Shared;

use GenericDatabase\Helpers\CustomException;
use ReflectionException;

trait Caller
{
    use Setter;
    use Getter;

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return mixed
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
     * @throws CustomException
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        if (method_exists(static::class, 'call')) {
            $instance = new static();
            $instance->call($name, $arguments);
            return $instance;
        }
        return null;
    }
}
