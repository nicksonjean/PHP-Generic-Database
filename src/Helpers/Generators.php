<?php

namespace GenericDatabase\Helpers;

use GenericDatabase\Core\Entity;

/**
 * The `GenericDatabase\Helpers\Generators` class provides methods
 * for generating options and determining the type of values.
 *
 * Example Usage:
 * <code>
 * $value = [
 *  'option1' => 'value1',
 *  'option2' => 'value2',
 *  'option3' => 'value3',
 * ];
 * $instance = new MyClass();
 * $className = 'MyClass';
 * $constantName = 'OPTIONS';
 * $attributes = ['option1', 'option2'];
 * $options = Generators::setConstant($value, $instance, $className, $constantName, $attributes);
 * </code>
 * `Output: $options will be ['option1' => 'value1', 'option2' => 'value2', 'ATTR3' => 'value3']`
 *
 * <code>
 * // Determine the type of a value
 * $value = '123';
 * $type = Generators::setType($value);
 * </code>
 * `Output: $type will be 123`
 *
 * Main functionalities:
 * - Setting a constant and generating options based on a provided value,
 * instance, class name, constant name, and attributes.
 * - Determining the type of value based on its characteristics.
 *
 * Methods:
 * - `setConstant($value, $instance, $className, $constantName, $attributes)`:
 * Sets a constant and generates options based on the provided value, instance,
 * class name, constant name, and attributes. Returns the generated options as an array.
 * - `setType($value)`:
 * Determines the type of value based on its characteristics.
 * Returns the determined type as a boolean, integer, or string.
 *
 * @package GenericDatabase\Helpers
 */
class Generators
{
    /**
     * Sets a constant and generates options based on the provided value,
     * instance, class name, constant name, and attributes.
     *
     * @param array $value The value to set the constant and generate options from.
     * @param mixed $instance The instance of the entity.
     * @param string $className The name of the class.
     * @param string $constantName The name of the constant.
     * @param array $attributes The attributes to check against.
     * @return array The generated options.
     */
    public static function setConstant(
        array $value,
        mixed $instance,
        string $className,
        string $constantName,
        array $attributes
    ): array {
        $options = [];

        foreach (Arrays::recombine(...$value) as $key => $value) {
            $index = str_replace("$className::", '', $key);
            $keyName = !in_array($index, $attributes) ? self::generateKeyName($index, $constantName) : $index;

            $instance->setAttribute($key, $value);

            if (!in_array($keyName, $attributes)) {
                $optionKey = constant(sprintf(Entity::CASE_INTERNAL_CLASS->value, $constantName, $className, $index));
                $instance->setOptions($optionKey, $value);
            }

            $options[self::generateOptionKey($className, $constantName, $index)] = $value;
        }

        return $options;
    }

    /**
     * Determines the type of value based on its characteristics.
     *
     * @param mixed $value The value to determine the type of.
     * @return bool|int|string The determined type of the value.
     */
    public static function setType(mixed $value): bool|int|string
    {
        $length = strlen((string) $value);
        $value ??= '';
        if (Validations::isNumber($value) && $length > 1) {
            $result = (int) $value;
        } elseif (($value === '0' || $value === '1') && $length === 1) {
            $result = (bool) $value;
        } elseif (Validations::isBoolean($value)) {
            $result = (bool) $value;
        } else {
            $result = $value;
        }
        return $result;
    }

    /**
     * Generates a key name based on the index and constant name.
     *
     * @param string $index The index to generate the key name from.
     * @param string $constantName The constant name to include in the key name.
     * @return string The generated key name.
     */
    private static function generateKeyName(string $index, string $constantName): string
    {
        return str_replace(
            "ATTR",
            $constantName === 'SQLite'
                ? mb_strtoupper($constantName) . '3'
                : mb_strtoupper($constantName),
            $index
        );
    }

    /**
     * Generates an option key based on the class name, constant name, and index.
     *
     * @param string $className The class name to include in the option key.
     * @param string $constantName The constant name to include in the option key.
     * @param string $index The index to include in the option key.
     * @return string The generated option key.
     */
    private static function generateOptionKey(string $className, string $constantName, string $index): string
    {
        return constant(sprintf(Entity::CASE_INTERNAL_CLASS->value, $constantName, $className, $index));
    }
}
