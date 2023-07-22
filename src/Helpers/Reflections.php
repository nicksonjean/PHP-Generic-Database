<?php

namespace GenericDatabase\Helpers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use GenericDatabase\Helpers\GenericException;

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
        } catch (GenericException $error) {
            $message = sprintf('Method %s not founded in the class %s', self::$defaultMethod, $class);
            throw new GenericException($message);
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
            $method = new ReflectionMethod($class, self::$defaultMethod);
            $result = ($method->isStatic()) ? true : false;
        } catch (GenericException) {
            $message = sprintf('Method %s not founded in the class %s', self::$defaultMethod, $class);
            throw new GenericException($message);
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
        return new ReflectionClass($class);
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
        return self::getClassInstance($class)->getProperty($prop)->getValue(null);
    }

    public static function createObjectAndSetPropertiesCaseInsenstive($aClassOrObject, array $aConstructorArgArray, array $aPropertyList)
    {
        $callConstructor = false;
        if (is_object($aClassOrObject)) {
            $result = $aClassOrObject;
            $reflector = new ReflectionObject($aClassOrObject);
        } else {
            if (!is_string($aClassOrObject))
                $aClassOrObject = '\stdClass';
            $classReflector = new ReflectionClass($aClassOrObject);
            if (method_exists($classReflector, 'newInstanceWithoutConstructor')) {
                $result = $classReflector->newInstanceWithoutConstructor();
                $callConstructor = true;
            } else {
                $result = $classReflector->newInstance($aConstructorArgArray);
            }
            $reflector = new ReflectionObject((object) $result);
        }
        $propertyReflections = $reflector->getProperties();
        foreach ($aPropertyList as $properyName => $propertyValue) {
            $createNewProperty = true;
            foreach ($propertyReflections as $propertyReflector) /* @var $propertyReflector ReflectionProperty */ {
                if (strcasecmp($properyName, $propertyReflector->name) == 0) {
                    $propertyReflector->setValue($result, $propertyValue);
                    $createNewProperty = false;
                    break;
                }
            }
            if ($createNewProperty) {
                $result->$properyName = $propertyValue;
            }
        }
        if ($callConstructor) {
            $constructorRefelector = $reflector->getConstructor();
            if ($constructorRefelector) {
                $constructorRefelector->invokeArgs($result, $aConstructorArgArray);
            }
        }
        return $result;
    }
}
