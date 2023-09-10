<?php

namespace GenericDatabase\Helpers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use GenericDatabase\Helpers\GenericException;

/**
 * The `GenericDatabase\Helpers\Reflections` class provides various reflection-based
 * methods to interact with classes and objects in PHP.
 * It allows you to perform tasks such as getting class instances, retrieving constants and properties,
 * creating objects, and setting properties in a case-insensitive manner.
 *
 * Example Usage:
 * <code>
 * // Get a singleton instance of a class
 * $instance = Translater::getSingletonInstance(MyClass::class);
 *
 * // Check if a method exists in a class
 * $exists = Translater::isSingletonMethodExits(MyClass::class);
 *
 * // Get a class instance
 * $classInstance = Translater::getClassInstance(MyClass::class);
 *
 * // Get all constants of a class
 * $constants = Translater::getClassConstants(MyClass::class);
 *
 * // Get the name of a constant by its value
 * $constantName = Translater::getClassConstantName(MyClass::class, $constantValue);
 *
 * // Get the value of a class property by its name
 * $propertyValue = Translater::getClassPropertyName(MyClass::class, 'propertyName');
 *
 * // Create an object and set properties in a case-insensitive manner
 * $object = Translater::createObjectAndSetPropertiesCaseInsensitive(MyClass::class, $constructorArgs, $propertyList);
 *
 * // Convert multiple arguments into an associative array
 * $argsArray = Translater::argsToArray($arg1, $arg2, $arg3);
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
 * - `getSingletonInstance($class)`:
 * Retrieves the singleton instance of a class by calling a static method.
 * Throws an exception if the method does not exist.
 * - `isSingletonMethodExits($class)`:
 * Checks if a method exists and is static in a class.
 * Throws an exception if the method does not exist or is not static.
 * - `getClassInstance($class)`:
 * Returns a `ReflectionClass` instance for the given class.
 * - `getClassConstants($class)`:
 * Returns an array of all constants defined in the class.
 * - `getClassConstantName($class, $field)`:
 * Returns the name of a constant by its value.
 * - `getClassPropertyName($class, $prop)`:
 * Returns the value of a class property by its name.
 * - `createObjectAndSetPropertiesCaseInsensitive($classOrObject, $constructorArgArray, $propertyList)`:
 * Creates an object and sets its properties in a case-insensitive manner.
 * - `argsToArray(...$args)`:
 * Converts multiple arguments into an associative array.
 *
 * Fields:
 * - `defaultMethod`:
 * A static field that stores the default method name (`getInstance`) used for getting singleton instances.
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
     * @return mixed The singleton instance of the class
     * @throws GenericException If the method does not exist in the class
     */
    public static function getSingletonInstance($class): mixed
    {
        try {
            $result = $class::{self::$defaultMethod}();
        } catch (GenericException $error) {
            $message = sprintf('Method %s not found in the class %s', self::$defaultMethod, $class);
            throw new GenericException($message);
        }
        return $result;
    }

    /**
     * Detect if method exists in class
     *
     * @param mixed $class The class object or instance
     * @return bool A boolean indicating if the method exists and is static
     * @throws GenericException If the method does not exist or is not static
     */
    public static function isSingletonMethodExists($class): bool
    {
        try {
            $method = new ReflectionMethod($class, self::$defaultMethod);
            $result = ($method->isStatic()) ? true : false;
        } catch (GenericException) {
            $message = sprintf('Method %s not found in the class %s', self::$defaultMethod, $class);
            throw new GenericException($message);
        }
        return $result;
    }

    /**
     * Get class instance
     *
     * @param mixed $class The class object or instance
     * @return mixed A ReflectionClass instance for the given class
     */
    public static function getClassInstance($class): mixed
    {
        return new ReflectionClass($class);
    }

    /**
     * Get all constants of the class
     *
     * @param mixed $class The class object or instance
     * @return mixed An array of all constants defined in the class
     */
    public static function getClassConstants($class): mixed
    {
        return self::getClassInstance($class)->getConstants();
    }

    /**
     * Get the name of a constant by its value
     *
     * @param mixed $class The class object or instance
     * @param mixed $field The constant name or value
     * @return mixed The name of a constant by its value
     */
    public static function getClassConstantName($class, $field): mixed
    {
        return array_search($field, (self::getClassInstance($class))->getConstants());
    }

    /**
     * Get the value of a class property by its name
     *
     * @param mixed $class The class object or instance
     * @param mixed $prop The property name
     * @return mixed The value of a class property by its name
     */
    public static function getClassPropertyName($class, $prop): mixed
    {
        return self::getClassInstance($class)->getProperty($prop)->getValue(null);
    }

    /**
     * Creates an object and sets its properties in a case-insensitive manner.
     *
     * @param mixed $classOrObject The class object or instance, or the class name as a string.
     * @param array $constructorArgArray An array of constructor arguments.
     * @param array $propertyList An array of properties to be set on the object.
     * @return mixed|object|string The created object with the properties set in a case-insensitive manner.
     * @throws \ReflectionException
     */
    public static function createObjectAndSetPropertiesCaseInsensitive(
        $classOrObject,
        array $constructorArgArray,
        array $propertyList
    ) {
        $result = self::createObject($classOrObject, $constructorArgArray);
        self::setPropertiesCaseInsensitive($result, $propertyList);

        return $result;
    }

    /**
     * Creates an object based on the given class or object.
     *
     * @param mixed $classOrObject The class object or instance, or the class name as a string.
     * @param array $constructorArgArray An array of constructor arguments.
     * @return mixed|object|string The created object.
     */
    private static function createObject($classOrObject, array $constructorArgArray)
    {
        if (is_object($classOrObject)) {
            return $classOrObject;
        }

        if (!is_string($classOrObject)) {
            $classOrObject = '\stdClass';
        }

        $classReflector = new ReflectionClass($classOrObject);

        if ($classReflector->hasMethod('newInstanceWithoutConstructor')) {
            return $classReflector->newInstanceWithoutConstructor(); //NOSONAR
        } else {
            return $classReflector->newInstance($constructorArgArray);
        }
    }

    /**
     * Sets the properties on the object in a case-insensitive manner.
     *
     * @param object $object The object to set the properties on.
     * @param array $propertyList An array of properties to be set on the object.
     * @return void
     */
    private static function setPropertiesCaseInsensitive($object, array $propertyList)
    {
        $reflector = new ReflectionObject($object);
        $propertyReflections = $reflector->getProperties();

        foreach ($propertyList as $propertyName => $propertyValue) {
            $propertyNameLower = mb_strtolower($propertyName);
            $propertyFound = false;

            foreach ($propertyReflections as $propertyReflection) {
                if (mb_strtolower($propertyReflection->name) === $propertyNameLower) {
                    $propertyReflection->setValue($object, $propertyValue); //NOSONAR
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
    public static function argsToArray(...$args)
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
