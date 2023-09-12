<?php

namespace GenericDatabase\Helpers;

class Types
{
    public static function setConstant($value, $instance, $className, $constantName, $attributes): array
    {
        $options = [];

        foreach (Arrays::recombine(...$value) as $key => $value) {
            $index = str_replace("$className::", '', $key);
            $keyName = !in_array($index, $attributes) ? self::generateKeyName($index, $constantName) : $index;

            $instance->setAttribute($key, $value);

            if (!in_array($keyName, $attributes)) {
                $optionKey = constant(sprintf("GenericDatabase\Engine\%s\%s::%s", $constantName, $className, $index));
                $instance->setOptions($optionKey, $value);
            }

            $options[self::generateOptionKey($className, $constantName, $index)] = $value;
        }

        return $options;
    }

    private static function generateKeyName($index, $constantName): string
    {
        return str_replace(
            "ATTR",
            $constantName === 'SQLite'
                ? mb_strtoupper($constantName) . '3'
                : mb_strtoupper($constantName),
            $index
        );
    }

    private static function generateOptionKey($className, $constantName, $index): string
    {
        return constant(sprintf("GenericDatabase\Engine\%s\%s::%s", $constantName, $className, $index));
    }

    public static function setType($value): bool|int|string
    {
        $length = strlen($value);
        $value = ($value === null) ? '' : $value;
        if (Validations::isNumber($value) && $length > 1) {
            $result = (int) $value;
        } elseif (($value === '0' || $value === '1') && $length === 1) {
            $result = (bool) $value;
        } elseif (Validations::isBoolean($value)) {
            $result = (bool) $value;
        } else {
            $result = (string) $value;
        }
        return $result;
    }
}
