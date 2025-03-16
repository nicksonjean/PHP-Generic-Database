<?php

namespace GenericDatabase\Shared;

/**
 * This trait is run when writing data to inaccessible (protected or private) or non-existing properties.
 *
 * Methods:
 * - `__set(string $name, mixed $value): void:` Magic method to set the value of inaccessible or non-existing properties.
 *
 * Fields:
 * - `$property`: Stores properties for dynamic property access.
 */
trait Setter
{
    use Property;

    /**
     * This method is run when writing data to inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @param mixed $value The value to be defined
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->property[$name] = $value;
    }
}
