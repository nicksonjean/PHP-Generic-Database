<?php

namespace GenericDatabase\Shared;

use GenericDatabase\Generic\Connection\SensitiveValue;

/**
 * This trait is utilized for reading data from inaccessible (protected or private) or non-existing properties.
 *
 * Methods:
 * - `__get(string $name): mixed:` Retrieves the value of a property if it exists, or returns null if the property is inaccessible or non-existent.
 *
 * Fields:
 * - `$property`: Stores properties for dynamic property access.
 */
trait Getter
{
    use Property;

    /**
     * Magic method to get the value of inaccessible or non-existing properties.
     *
     * @param string $name Property name
     * @return mixed The property value
     */
    public function __get(string $name): mixed
    {
        if (isset($this->property[$name])) {
            if ($this->property[$name] instanceof SensitiveValue) {
                return $this->property[$name]->getValue();
            }
            return $this->property[$name];
        }
        return null;
    }
}

