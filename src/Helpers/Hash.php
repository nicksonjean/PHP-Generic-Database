<?php

namespace GenericDatabase\Helpers;

use Random\RandomException;

/**
 * The `GenericDatabase\Helpers\Hash` class provides a method to generate a hash using a specified algorithm.
 *
 * Method:
 * - `hash(string $type = 'sha512', int $length = 64): object:` Generates a hash using the specified algorithm and length
 *
 * @package GenericDatabase\Helpers
 * @subpackage Hash
 */
class Hash
{
    /**
     * Generates a hash using the specified algorithm and length.
     *
     * @param string $type The hash algorithm to use (default is 'sha512').
     * @param int $length The length of the random bytes to generate (default is 64).
     * @return string The generated hash.
     * @throws RandomException
     */
    public static function hash(string $type = 'sha512', int $length = 64): string
    {
        return hash($type, random_bytes($length) . uniqid(mt_rand(), true) . microtime(true));
    }
}
