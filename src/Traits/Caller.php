<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Setter;
use GenericDatabase\Traits\Getter;
use GenericDatabase\Helpers\Reflections;

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
        $field = strtolower(substr($name, 3));
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
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        if (Reflections::isSingletonMethodExists(__CLASS__)) {
            $instance = Reflections::getSingletonInstance(__CLASS__);
            $instance::call($name, $arguments);
            return $instance;
        }
        return null;
    }
}
