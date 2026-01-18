<?php

namespace GenericDatabase\Generic\QueryBuilder;

/**
 * The QueryObject class represents a query object used for building and managing database queries. It has an internal storage array ($property)
 * to store query object data and a list of valid properties ($validProperties) that can be used within the class.
 *
 * Methods:
 * - `__get($name): mixed:` Returns a reference to the property value if it is a valid property, initializes it as an empty array if it doesn't exist, or returns null if it's not a valid property.
 * - `__set($name, $value): void:` Sets the value of a valid property, removes the property from storage if the value is empty, or does nothing if the property is not valid.
 * - `__isset($name): bool:` Returns true if the property exists in the storage array, false otherwise.
 * - `__debugInfo(): array:` Returns the internal storage array for debugging purposes.
 *
 * Fields:
 * - `$property`: Stores properties for dynamic property access.
 */
class QueryObject
{
    /**
     * Internal storage for query object data, this property is used to store various elements or parameters
     * related to the query object. It is an associative array that
     * holds the internal state of the query builder.
     *
     * @var array
     */
    private array $property = [];

    /**
     * @var array $validProperties
     *
     * A static array containing the list of valid properties
     * that can be used within the QueryObject class. This is
     * used to validate and ensure only predefined properties
     * are allowed in the query building process.
     */
    private static array $validProperties = [
        'select',
        'from',
        'join',
        'on',
        'where',
        'having',
        'group',
        'order',
        'limit'
    ];

    /**
     * Constructor for the QueryObject class.
     * Initializes a new instance of the QueryObject.
     */
    public function __construct()
    {
    }

    /**
     * Magic getter method to access dynamic properties, this method returns a reference to the property specified by $name if it is
     * a valid property. If the property does not exist in the storage, it initializes
     * it as an empty array. If $name is not a valid property, it returns null.
     *
     * @param string $name The name of the property to retrieve.
     * @return mixed A reference to the property value if valid, or null if not.
     */

    /**
     * Magic getter method to access dynamic properties, this method returns a reference to the property specified by $name if it is
     * a valid property. If the property does not exist in the storage, it initializes
     * it as an empty array. If $name is not a valid property, it returns null.
     *
     * @param string $name The name of the property to retrieve.
     * @return mixed A reference to the property value if valid, or null if not.
     */
    public function &__get(string $name): mixed
    {
        if (in_array($name, self::$validProperties)) {
            if (!isset($this->property[$name])) {
                $this->property[$name] = [];
            }
            return $this->property[$name];
        }

        $null = null;
        return $null;
    }

    /**
     * Magic setter method to dynamically set properties, this method sets the value of $name if it is a valid property.
     * If the value is not empty, it sets the value in the storage, otherwise it removes the property from the storage
     * if it exists.
     *
     * @param string $name The name of the property to set.
     * @param mixed $value The value to assign to the property.
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, self::$validProperties)) {
            if (!empty($value)) {
                $this->property[$name] = $value;
            } else {
                unset($this->property[$name]);
            }
        }
    }

    /**
     * Magic isset method to check if a dynamic property exists, this method is triggered by calling isset() or empty() on
     * inaccessible (protected or private) or non-existing properties. It returns true if the property exists, false otherwise.
     *
     * @param string $name The name of the property to check.
     * @return bool True if the property exists, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return isset($this->property[$name]);
    }

    /**
     * Magic method for debugging and returns the storage array used for storing the QueryObject properties.
     *
     * @return array The storage array.
     */
    public function __debugInfo(): array
    {
        return $this->property;
    }
}

