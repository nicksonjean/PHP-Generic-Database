<?php

namespace GenericDatabase\Helpers\Parsers;

use Exception;
use Nette\Neon\Neon as NeonParser;
use Nette\Neon\Exception as NeonException;

/**
 * The `GenericDatabase\Helpers\Parsers\NEON` class provides methods for working with NEON data.
 * The isValidNEON method checks if a given string is a valid NEON, and the parseNEON method parses
 * a valid NEON string into an array.
 *
 * Main functionalities:
 * - Check if a string is a valid NEON
 * - Parse a valid NEON string into an array
 * - Parse NEON table content (array of rows) from string
 * - Encode array of rows to NEON format
 *
 * Methods:
 * - `isValidNEON($neon)`: Checks if a given string is a valid NEON. Returns a boolean value indicating the result.
 * - `parseNEON($neon)`: Parses a valid NEON string into an array. Returns the parsed NEON data as an array.
 * - `parseTableNeonString($content)`: Parses NEON string content into array of rows.
 * - `encodeTableToNeon($data, $flags)`: Encodes array of rows to NEON format string.
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
            NeonParser::decode($content);
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
            return (array) NeonParser::decode($content);
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Parse NEON string content (tabular format - array of objects) into array of rows.
     * Uses Nette\Neon\Neon::decode. Compatible with JSON/CSV-like structure.
     *
     * @param string $content The NEON string content.
     * @return array Array of rows (associative arrays).
     */
    public static function parseTableNeonString(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        try {
            $parsed = NeonParser::decode($content);
            if (!is_array($parsed)) {
                return [];
            }
            $rows = [];
            foreach ($parsed as $row) {
                if (is_array($row) || $row instanceof \stdClass) {
                    $rows[] = (array) $row;
                }
            }
            return $rows;
        } catch (NeonException) {
            return [];
        }
    }

    /**
     * Encode array of rows to NEON format using Nette\Neon\Neon::encode.
     *
     * @param array $data Array of rows (associative arrays).
     * @param int $flags 0 = single line, 1 = block mode (pretty).
     * @return string NEON format string.
     */
    public static function encodeTableToNeon(array $data, int $flags = 0): string
    {
        try {
            return NeonParser::encode($data, $flags === 1);
        } catch (NeonException) {
            return '[]';
        }
    }
}
