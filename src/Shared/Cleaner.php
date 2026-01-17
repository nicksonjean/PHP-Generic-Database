<?php

namespace GenericDatabase\Shared;

/**
 * This trait provides magic methods to handle `__isset` and `unset` operations on inaccessible or non-existing properties within a class.
 *
 * Methods:
 * - `__isset(string $name): bool:` Checks if a property is set.
 * - `__unset(string $name): void:` Unsets a property.
 *
 * Fields:
 * - `$property`: Stores properties for dynamic property access.
 */
trait Cleaner
{
    use Property;

    /**
     * This method is triggered by calling isset() or empty() on
     * inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->property[$name]);
    }

    /**
     * This method is invoked when unset() is used on inaccessible
     * (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->property[$name]);
    }
}
