<?php

namespace GenericDatabase\Helpers;

/**
 * Class Hash
 *
 * Provides a method to generate a hash using a specified algorithm.
 * 
 * @package GenericDatabase\Helpers
 */
class Hash
{
    /**
     * Generates a hash using the specified algorithm and length.
     *
     * @param string $type The hash algorithm to use (default is 'sha512').
     * @param int $length The length of the random bytes to generate (default is 64).
     * @return string The generated hash.
     */
    public static function hash($type = 'sha512', $length = 64): string
    {
        return hash($type, random_bytes($length) . uniqid(mt_rand(), true) . microtime(true));
    }
}
