<?php

namespace GenericDatabase\Helpers;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;

/**
 * The `GenericDatabase\Helpers\Reflections` class provides various reflection-based
 * methods to interact with classes and objects in PHP.
 * It allows you to perform tasks such as getting class instances, retrieving constants and properties,
 * creating objects, and setting properties in a case-insensitive manner.
 *
 * Example Usage:
 * <code>
 * // Get a singleton instance of a class
 * $instance = Reflections::getSingletonInstance(MyClass::class);
 *
 * // Check if a method exists in a class
 * $exists = Reflections::isSingletonMethodExists(MyClass::class);
 *
 * // Get a class instance
 * $classInstance = Reflections::getClassInstance(MyClass::class);
 *
 * // Get all constants of a class
 * $constants = Reflections::getClassConstants(MyClass::class);
 *
 * // Get the name of a constant by its value
 * $constantName = Reflections::getClassConstantName(MyClass::class, $constantValue);
 *
 * // Get the value of a class property by its name
 * $propertyValue = Reflections::getClassPropertyName(MyClass::class, 'propertyName');
 *
 * // Create an object and set properties in a case-insensitive manner
 * $object = Reflections::createObjectAndSetPropertiesCaseInsensitive(MyClass::class, $constructorArgs, $propertyList);
 *
 * // Convert multiple arguments into an associative array
 * $argsArray = Reflections::argsToArray($arg1, $arg2, $arg3);
 * </code>
 *
 * Main functionalities:
 * - Get the singleton instance of a class by calling a static method
 * - Check if a method exists and is static in a class
 * - Get a ReflectionClass instance for a class
 * - Get all constants defined in a class
 * - Get the name of a constant by its value
 * - Get the value of a class property by its name
 * - Create objects and set properties in a case-insensitive manner
 * - Convert multiple arguments into an associative array
 *
 * Methods:
 * - `getSingletonInstance($class)`: Retrieves the singleton instance of a class by calling a static method. Throws an exception if the method does not exist.
 * - `isSingletonMethodExists($class)`: Checks if a method exists and is static in a class. Throws an exception if the method does not exist or is not static.
 * - `getClassInstance($class)`: Returns a `ReflectionClass` instance for the given class.
 * - `getClassConstants($class)`: Returns an array of all constants defined in the class.
 * - `getClassConstantName($class, $field)`: Returns the name of a constant by its value.
 * - `getClassPropertyName($class, $prop)`: Returns the value of a class property by its name.
 * - `createObjectAndSetPropertiesCaseInsensitive($classOrObject, $constructorArgArray, $propertyList)`: Creates an object and sets its properties in a case-insensitive manner.
 * - `argsToArray(...$args)`: Converts multiple arguments into an associative array.
 *
 * Fields:
 * - `defaultMethod`: A static field that stores the default method name (`getInstance`) used for getting singleton instances.
 *
 * @package GenericDatabase\Helpers
 * @subpackage Reflections
 */
class Reflections
{
    /**
     * Set default method
     * @var string $defaultMethod = 'getInstance'
     */
    public static string $defaultMethod = 'getInstance';

    /**
     * Get singleton instance
     *
     * @param mixed $class The class object or instance
     * @return object|false The singleton instance of the class
     * @throws Exceptions If the method does not exist in the class
     */
    public static function getSingletonInstance(mixed $class): object|false
    {
        if (!method_exists($class, self::$defaultMethod)) {
            return false;
        }
        return $class::{self::$defaultMethod}();
    }

    /**
     * Detect if method exists in class
     *
     * @param mixed $class The class object or instance
     * @return bool A boolean indicating if the method exists and is static
     * @throws ReflectionException
     */
    public static function isSingletonMethodExists(mixed $class): bool
    {
        return (bool) new ReflectionMethod($class, self::$defaultMethod);
    }

    /**
     * Get class instance
     *
     * @param mixed $class The class object or instance
     * @return ReflectionClass A ReflectionClass instance for the given class
     * @throws ReflectionException
     */
    public static function getClassInstance(mixed $class): ReflectionClass
    {
        return new ReflectionClass($class);
    }

    /**
     * Get all constants of the class
     *
     * @param mixed $class The class object or instance
     * @return array An array of all constants defined in the class
     * @throws ReflectionException
     */
    public static function getClassConstants(mixed $class): array
    {
        return self::getClassInstance($class)->getConstants();
    }

    /**
     * Get the name of a constant by its value
     *
     * @param mixed $class The class object or instance
     * @param mixed $field The constant name or value
     * @return string|int|bool The name of a constant by its value
     * @throws ReflectionException
     */
    public static function getClassConstantName(mixed $class, mixed $field): string|int|bool
    {
        return array_search($field, (self::getClassInstance($class))->getConstants());
    }

    /**
     * Get the value of a class property by its name
     *
     * @param mixed $class The class object or instance
     * @param mixed $prop The property name
     * @return mixed The value of a class property by its name
     * @throws ReflectionException
     */
    public static function getClassPropertyName(mixed $class, mixed $prop): mixed
    {
        return self::getClassInstance($class)->getProperty($prop)->getValue();
    }

    /**
     * Creates an object and sets its properties in a case-insensitive manner.
     *
     * @param mixed $classOrObject The class object or instance, or the class name as a string.
     * @param array $constructorArgArray An array of constructor arguments.
     * @param array $propertyList An array of properties to be set on the object.
     * @return mixed The created object with the properties set in a case-insensitive manner.
     * @throws ReflectionException
     */
    public static function createObjectAndSetPropertiesCaseInsensitive(
        mixed $classOrObject,
        array $constructorArgArray,
        array $propertyList
    ): mixed {
        $result = self::createObject($classOrObject, $constructorArgArray);
        self::setPropertiesCaseInsensitive($result, $propertyList);

        return $result;
    }

    /**
     * Creates an object based on the given class or object.
     *
     * @param mixed $classOrObject The class object or instance, or the class name as a string.
     * @return ReflectionObject The created object.
     */
    private static function createReflectionObject(mixed $classOrObject): ReflectionObject
    {
        return new ReflectionObject($classOrObject);
    }

    /**
     * Creates an object based on the given class or object.
     *
     * @param mixed $classOrObject The class object or instance, or the class name as a string.
     * @param array $constructorArgArray An array of constructor arguments.
     * @return mixed The created object.
     * @throws ReflectionException
     */
    private static function createObject(mixed $classOrObject, array $constructorArgArray): mixed
    {
        if (is_object($classOrObject)) {
            $result = self::createReflectionObject($classOrObject);
        } else {
            if (!is_string($classOrObject)) {
                $classOrObject = '\stdClass';
            }
            $classReflector = new ReflectionClass($classOrObject);
            if (method_exists($classReflector, 'newInstanceWithoutConstructor') && empty($constructorArgArray)) {
                $result = $classReflector->newInstanceWithoutConstructor(); //NOSONAR
            } else {
                $result = $classReflector->newInstance($constructorArgArray);
            }
        }
        return $result;
    }

    /**
     * Sets the properties on the object in a case-insensitive manner.
     *
     * @param object $object The object to set the properties on.
     * @param array $propertyList An array of properties to be set on the object.
     * @return void
     */
    private static function setPropertiesCaseInsensitive(object $object, array $propertyList): void
    {
        $reflector = self::createReflectionObject($object);
        $propertyReflections = $reflector->getProperties();

        foreach ($propertyList as $propertyName => $propertyValue) {
            $propertyNameLower = mb_strtolower($propertyName);
            $propertyFound = false;

            foreach ($propertyReflections as $propertyReflection) {
                if (mb_strtolower($propertyReflection->name) === $propertyNameLower) {
                    $propertyReflection->setValue($object, $propertyValue);
                    $propertyFound = true;
                    break;
                }
            }

            if (!$propertyFound) {
                $object->$propertyName = $propertyValue;
            }
        }
    }

    /**
     * Converts multiple arguments into an associative array.
     *
     * @param mixed ...$args The arguments to convert.
     * @return array The converted associative array.
     */
    public static function argsToArray(mixed ...$args): array
    {
        $result = [];
        foreach ($args as $arg) {
            foreach ($arg as $name => $value) {
                $result[$name] = $value;
            }
        }
        return $result;
    }
}
