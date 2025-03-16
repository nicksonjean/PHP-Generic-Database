<?php

namespace GenericDatabase\Shared;

use ReflectionEnum;
use ReflectionException;
use Exception;
use UnitEnum;

/**
 * This trait provides utility methods for handling enum cases.
 *
 * Methods:
 * - `names(): array:` Returns an array of enum case names.
 * - `values(): array:` Returns an array of enum case values.
 * - `array(): array:` Combines enum values and names into an associative array.
 * - `casesIndexedByName(): array:` Returns an associative array of enum cases indexed by their names.
 * - `isValidCase(string $name): bool:` Checks if a given name is a valid enum case.
 * - `fromName(string $name): self:` Retrieves the enum case by name, throws an exception if not found.
 * @method static cases()
 */
trait Enumerator
{
    /**
     * @return array
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array
     */
    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }

    /**
     * @return array
     */
    public static function casesIndexedByName(): array
    {
        return array_combine(array_map(fn(self $case) => $case->name, self::cases()), self::cases());
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidCase(string $name): bool
    {
        $reflector = new ReflectionEnum(self::class);
        try {
            $reflector->getCase($name);
        } catch (ReflectionException) {
            return false;
        }
        return true;
    }


    /**
     * @param string $name
     * @return UnitEnum
     * @throws Exception
     */
    public static function fromName(string $name): UnitEnum
    {
        $reflector = new ReflectionEnum(self::class);
        try {
            $enumReflector = $reflector->getCase($name);
            return $enumReflector->getValue();
        } catch (ReflectionException) {
            throw new Exception(sprintf('Undefined enum name %s::%s', self::class, $name));
        }
    }
}
