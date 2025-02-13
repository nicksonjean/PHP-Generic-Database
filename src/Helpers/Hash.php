<?php

namespace GenericDatabase\Helpers;

class Hash
{
    public static function hash($type = 'sha512', $length = 64): string
    {
        return hash($type, random_bytes($length) . uniqid(mt_rand(), true) . microtime(true));
    }
}
