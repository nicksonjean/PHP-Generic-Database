<?php

namespace GenericDatabase\Helpers;

use Exception;

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
     * @return mixed
     */
    public static function getSingletonInstance($class): mixed
    {
        try {
            $result = call_user_func($class . '::' . self::$defaultMethod);
        } catch (Exception $e) {
            $message = sprintf('Method %s not founded in the class %s', self::$defaultMethod, $class);
            throw new Exception($message);
        }
        return $result;
    }

    /**
     * Detect if method exists in class
     *
     * @param mixed $class The class object or instance
     * @return mixed
     */
    public static function isSingletonMethodExits($class): mixed
    {
        try {
            $method = new \ReflectionMethod($class, self::$defaultMethod);
            $result = ($method->isStatic()) ? true : false;
        } catch (Exception) {
            $message = sprintf('Method %s not founded in the class %s', self::$defaultMethod, $class);
            throw new Exception($message);
        }
        return $result;
    }

    /**
     * Get class instance
     *
     * @param mixed $class The class object or instance
     * @return mixed
     */
    public static function getClassInstance($class): mixed
    {
        return new \ReflectionClass($class);
    }

    /**
     * Get all constants of the class
     *
     * @param mixed $class The class object or instance
     * @return mixed
     */
    public static function getClassConstants($class): mixed
    {
        return self::getClassInstance($class)->getConstants();
    }

    /**
     * Get all constants of the class by name and value
     *
     * @param mixed $class The class object or instance
     * @param mixed $field Get the constant name data
     * @return mixed
     */
    public static function getClassConstantName($class, $field): mixed
    {
        return array_flip((self::getClassInstance($class))->getConstants())[$field];
    }

    /**
     * Get all property of the class by name
     *
     * @param mixed $class The class object or instance
     * @param mixed $prop Get the property name data
     * @return mixed
     */
    public static function getClassPropertyName($class, $prop): mixed
    {
        $reflection = self::getClassInstance($class);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue(null);
    }
}
