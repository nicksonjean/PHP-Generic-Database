<?php

namespace GenericDatabase\Generic\Connection;

use GenericDatabase\Shared\Property;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Cleaner;

/**
 * This class provides a configuration container using magic methods
 * for accessing and modifying properties. It utilizes traits for handling
 * property access, setting, checking existence, and unsetting.
 *
 * Methods:
 * - `__get(string $name): mixed`: Magic getter method
 * - `__set(string $name, mixed $value): void`: Magic setter method to dynamically set properties.
 * - `__isset(string $name): bool`: This method is triggered by calling isset() or empty() on inaccessible (protected or private) or non-existing properties.
 * - `__unset(string $name): void`: This method is invoked when unset() is used on inaccessible (protected or private) or non-existing properties.
 * - `__construct(array $property = [])`: Constructor to initialize the Settings object.
 * - `__debugInfo(): array`: Magic method for debugging.
 *
 * Fields:
 * - `$property`: Stores properties for dynamic property access.
 */
class Settings
{
    use Property;
    use Getter;
    use Setter;
    use Cleaner;

    /**
     * Constructor to initialize the Settings object.
     *
     * @param array $property An array for initializing the property values.
     */

    public function __construct(array $property = [])
    {
        $this->property = $property;
    }

    /**
     * Magic method for debugging.
     *
     * Returns the property array used for storing settings.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->property;
    }
}
