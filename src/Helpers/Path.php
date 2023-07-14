<?php

namespace GenericDatabase\Helpers;

use GenericDatabase\Helpers\GenericException;

class Path
{
    /**
     * Convert path from relative to absolute
     *
     * @param string $path The relative path
     * @return string
     */
    public static function toAbsolute($path): string
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        $search = explode('/', $path);
        $search = array_filter($search, function ($part) {
            return $part !== '.';
        });
        $append = array();
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
     * @return bool
     */
    public static function isAbsolute($path): bool
    {
        if (!is_string($path)) {
            $message = sprintf('String expected but was given %s', gettype($path));
            throw new GenericException($message);
        }
        if (!ctype_print($path)) {
            $message = 'Path can NOT have non-printable characters or be empty';
            throw new GenericException($message);
        }
        $regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';
        $regExp .= '(?<root>(?:[[:alpha:]]:/|/)?)';
        $regExp .= '(?<path>(?:[[:print:]]*))$%';
        $parts = [];
        if (!preg_match($regExp, $path, $parts)) {
            $message = sprintf('Path is NOT valid, was given %s', $path);
            throw new GenericException($message);
        }
        if ('' !== $parts['root']) {
            return true;
        }
        return false;
    }
}
