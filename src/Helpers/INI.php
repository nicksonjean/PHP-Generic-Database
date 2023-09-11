<?php

namespace GenericDatabase\Helpers;

class INI
{
    /**
     * Detect if ini is valid
     *
     * @param mixed $ini Argument to be tested
     * @return bool
     */
    public static function isValidINI(mixed $ini): bool
    {
        if (!is_string($ini)) {
            return false;
        }
        return str_ends_with($ini, 'ini') && (parse_ini_file($ini));
    }

    /**
     * Parse a valid ini
     *
     * @param string $ini Argument to be parsed
     * @return array
     */
    public static function parseINI(string $ini): array
    {
        return (array) parse_ini_file($ini, false, INI_SCANNER_TYPED);
    }
}
