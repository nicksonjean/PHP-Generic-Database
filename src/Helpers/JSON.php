<?php

namespace GenericDatabase\Helpers;

class JSON
{
    /**
     * Detect if json is valid
     *
     * @param mixed $json Argument to be tested
     * @return bool
     */
    public static function isValidJSON(mixed $json): bool
    {
        if (!is_string($json)) {
            return false;
        }
        set_error_handler(fn (): bool => true, E_WARNING);
        json_decode(file_get_contents($json));
        if (json_last_error() === JSON_ERROR_NONE) {
            restore_error_handler();
            return true;
        }
        restore_error_handler();
        return false;
    }

    /**
     * Parse a valid json
     *
     * @param string $json Argument to be parsed
     * @return array
     */
    public static function parseJSON(string $json): array
    {
        return (array) json_decode(file_get_contents($json), true);
    }
}
