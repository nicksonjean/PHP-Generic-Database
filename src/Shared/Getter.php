<?php

namespace GenericDatabase\Shared;

/**
 * Methods:
 * - `__get(string $name): mixed:` Retrieves the value of a property if it exists, or returns null if the property is inaccessible or non-existent.
 */
trait Getter
{
    use Property;

    /**
     * This method is utilized for reading data from inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->property[$name] ?? null;
    }
}
