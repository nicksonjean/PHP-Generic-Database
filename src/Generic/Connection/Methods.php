<?php

namespace GenericDatabase\Generic\Connection;

use GenericDatabase\Generic\Connection\Settings;

trait Methods
{
    /**
     * Property to store settings
     * @var Settings $property
     */
    public ?Settings $property = null;

    public function __construct()
    {
        $this->property = new Settings();
    }

    /**
     * Magic method to access dynamic properties
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->property->{$name} ?? null;
    }

    /**
     * Magic method to set dynamic properties
     *
     * @param string $name Property name
     * @param mixed $value Value to be assigned
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if ($this->property === null) {
            $this->property = new Settings();
        }

        $this->property->{$name} = $value;
    }

    /**
     * Magic method to check if a property exists
     *
     * @param string $name Property name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->property->{$name});
    }

    /**
     * Magic method to remove a property
     *
     * @param string $name Property name
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->property->{$name});
    }

    /**
     * Magic method for serialization
     *
     * @return array
     */
    public function __sleep(): array
    {
        return ['property'];
    }

    /**
     * Magic method for deserialization
     *
     * @return void
     */
    public function __wakeup(): void
    {
        $this->property = new Settings((array) $this->property);
    }
}
