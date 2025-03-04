<?php

namespace GenericDatabase\Generic\Connection;

use GenericDatabase\Shared\Property;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Cleaner;

/**
 * Class Settings provides a configuration container using magic methods
 * for accessing and modifying properties. It utilizes traits for handling
 * property access, setting, checking existence, and unsetting.
 *
 * @package GenericDatabase\Generic\Connection
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
