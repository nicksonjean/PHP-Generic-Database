<?php

namespace GenericDatabase\Generic\Connection;

/**
 * This trait provides magic methods for dynamic property access and manipulation
 * and also includes methods for getting, setting, checking existence, and unsetting
 * properties dynamically. It also handles serialization and deserialization of the
 * property using the `__sleep` and `__wakeup` magic methods.
 *
 * Methods:
 * - `__construct(array $property = [])`: Constructor to initialize the Settings object.
 * - `__get(string $name): mixed`: Magic getter method
 * - `__set(string $name, mixed $value): void`: Magic setter method to dynamically set properties.
 * - `__isset(string $name): bool`: This method is triggered by calling isset() or empty() on inaccessible (protected or private) or non-existing properties.
 * - `__unset(string $name): void`: This method is invoked when unset() is used on inaccessible (protected or private) or non-existing properties.
 * - `__sleep(): array`: Sleep instance used by serialize/unserialize
 * - `__wakeup(): void`: Wakeup instance used by serialize/unserialize
 *
 * Fields:
 * - `$property`: Stores settings for dynamic property access.
 */
trait Methods
{
    /**
     * Property to store settings
     * @var Settings|null $property
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

