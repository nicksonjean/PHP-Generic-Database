<?php

namespace GenericDatabase\Traits;

use
  GenericDatabase\Traits\Regex,

  GenericDatabase\Traits\Arrays;

trait Types
{
    public static function setConstant($value, $instance, $className, $constantName, $attributes): array
    {
        $options = [];
        foreach (Arrays::recombine(...$value) as $key => $value) {
            $index = str_replace("$className::", '', $key);
            $key_name = !in_array($index, $attributes) ? str_replace("ATTR", $constantName === 'SQLite' ? strtoupper($constantName) . '3' : strtoupper($constantName), $index) : $index;
            $instance->setAttribute($key, $value);
            if (!in_array($key_name, $attributes)) {
                $instance->setOptions(constant($key_name), $value);
            }
            $options[constant(sprintf("GenericDatabase\Engine\%s\%s::%s", $constantName, $className, $index))] = $value;
        }
        return $options;
    }

    public static function setType($value)
    {
        $length = strlen($value);
        $value = ($value === null) ? '' : $value;
        if (Regex::isNumber($value) && $length > 1) {
            $result = (int) $value;
        } elseif (($value === '0' or $value === '1') && $length === 1) {
            $result = (bool) $value;
        } elseif (Regex::isBoolean($value)) {
            $result = (bool) $value;
        } else {
            $result = (string) $value;
        }
        return $result;
    }
}
