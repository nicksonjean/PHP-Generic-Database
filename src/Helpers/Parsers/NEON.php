<?php

namespace GenericDatabase\Helpers\Parsers;

use Exception;
use Nette\Neon\Neon as Neom;

/**
 * The `GenericDatabase\Helpers\Parsers\NEON` class provides two static methods for working with NEON data.
 * The isValidNEON method checks if a given string is a valid NEON, and the parseNEON method parses
 * a valid NEON string into an array.
 *
 * Main functionalities:
 * - Check if a string is a valid NEON
 * - Parse a valid NEON string into an array
 *
 * Methods:
 * - `isValidNEON($neon)`: Checks if a given string is a valid NEON. Returns a boolean value indicating the result.
 * - `parseNEON($neon)`: Parses a valid NEON string into an array. Returns the parsed NEON data as an array.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage NEON
 */
class NEON
{
    /**
     * Check if neon string is valid
     *
     * @param mixed $neon Argument to be tested
     * @return bool
     */
    public static function isValidNEON(mixed $neon): bool
    {
        if (!is_string($neon)) {
            return false;
        }

        if (!str_ends_with($neon, 'neon')) {
            return false;
        }

        try {
            $content = file_get_contents($neon);
            Neom::decode($content);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Parse a valid neon string
     *
     * @param string $neon Argument to be parsed
     * @return array
     */
    public static function parseNEON(string $neon): array
    {
        try {
            $content = file_get_contents($neon);
            return (array) Neom::decode($content);
        } catch (Exception) {
            return [];
        }
    }
}

