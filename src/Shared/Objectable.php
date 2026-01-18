<?php

namespace GenericDatabase\Shared;

/**
 * This trait provides magic methods for dynamic property access and manipulation, including getter, setter, isset, and unset operations. Supports serialization
 * and deserialization through `__sleep` and `__wakeup` methods. Converts object properties to an associative array with the toArray method.
 *
 * Methods:
 * - `__get(string $name): mixed:` Magic getter method
 * - `__set(string $name, mixed $value): void:` Magic setter method to dynamically set properties.
 * - `__isset(string $name): bool:` This method is triggered by calling isset() or empty() on inaccessible (protected or private) or non-existing properties.
 * - `__unset(string $name): void:` This method is invoked when unset() is used on inaccessible (protected or private) or non-existing properties.
 * - `__sleep(): array:` Sleep instance used by serialize/unserialize
 * - `__wakeup(): void:` Wakeup instance used by serialize/unserialize
 * - `toArray(): array:` Returns the object properties as an associative array
 */
trait Objectable
{
    /**
     * Magic getter method
     *
     * @param string $name Name of the property to access
     * @return mixed|null The value of the property, or null if it does not exist
     */
    public function __get(string $name): mixed
    {
        return $this->$name ?? ($this->$name = new self());
    }

    /**
     * Magic setter method to dynamically set properties.
     *
     * If the property is an array and already exists as an instance of the current class,
     * it will recursively set each key-value pair on the existing instance.
     * Otherwise, it assigns the value directly to the property.
     *
     * @param string $name Name of the property to set
     * @param mixed $value Value to assign to the property
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (is_array($value) && isset($this->$name) && $this->$name instanceof self) {
            foreach ($value as $k => $v) {
                $this->$name->$k = $v;
            }
        } else {
            $this->$name = $value;
        }
    }

    /**
     * This method is triggered by calling isset() or empty() on
     * inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->$name);
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
        unset($this->$name);
    }

    /**
     * Sleep instance used by serialize/unserialize
     *
     * @return array
     */
    public function __sleep(): array
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * Wakeup instance used by serialize/unserialize
     *
     * @return void
     */
    public function __wakeup(): void {}

    /**
     * Returns the object properties as an associative array
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            $result[$key] = ($value instanceof self) ? $value->toArray() : $value;
        }
        return $result;
    }
}

