<?php

namespace GenericDatabase\Helpers;

/**
 * The `GenericDatabase\Helpers\Path` class provides two static methods for working with file paths:
 * `toAbsolute` and `isAbsolute`. The `toAbsolute` method converts a relative path to an absolute path,
 * while the `isAbsolute` method checks if a given path is absolute or not.
 *
 * Example Usage:
 * <code>
 * // Convert a relative path to an absolute path
 * $relativePath = 'path/to/file.txt';
 * $absolutePath = Path::toAbsolute($relativePath);
 * echo $absolutePath;
 * </code>
 * `Output: /full/path/to/file.txt`
 *
 * <code>
 * // Check if a path is absolute
 * $path = '/full/path/to/file.txt';
 * $isAbsolute = Path::isAbsolute($path);
 * echo $isAbsolute ? 'Absolute path' : 'Relative path';
 * </code>
 * `Output: Absolute path`
 *
 * Main functionalities:
 * - The `toAbsolute` method converts a relative path to an absolute path by replacing the directory separator with a forward slash ('/') and removing any occurrences of '.' (current directory).
 * - The `isAbsolute` method checks if a path is absolute by using regular expressions to match the path against a pattern that includes optional wrappers (e.g., 'http://') and a root (e.g., 'C:/') followed by the path itself.
 *
 * Methods:
 * - `toAbsolute(string $path): string`: Converts a relative path to an absolute path.
 * - `isAbsolute(string $path): bool`: Checks if a path is absolute.
 *
 * @package GenericDatabase\Helpers
 * @subpackage Path
 */
class Path
{
    /**
     * Convert path from relative to absolute
     *
     * @param string $path The relative path
     * @return string The absolute path
     */
    public static function toAbsolute(string $path): string
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        $search = explode('/', $path);
        $search = array_filter($search, fn($part) => $part !== '.');
        $append = [];
        $match = false;
        while (count($search) > 0) {
            $match = realpath(implode('/', $search));
            if ($match !== false) {
                break;
            }
            array_unshift($append, array_pop($search));
        }
        if ($match === false) {
            $match = getcwd();
        }
        if (count($append) > 0) {
            $match .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $append);
        }
        return $match;
    }

    /**
     * Detect if path is absolute
     *
     * @param string $path The path
     * @return bool True if the path is absolute, false otherwise
     * @throws Exceptions If the path contains non-printable characters or is empty
     */
    public static function isAbsolute(string $path): bool
    {
        if (!ctype_print($path)) {
            $message = 'Path can NOT have non-printable characters or be empty';
            throw new Exceptions($message);
        }
        $regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';
        $regExp .= '(?<root>(?:[[:alpha:]]:/|/)?)';
        $regExp .= '(?<path>(?:[[:print:]]*))$%';
        $parts = [];
        preg_match($regExp, $path, $parts);
        if ('' !== $parts['root']) {
            return true;
        }
        return false;
    }
}

