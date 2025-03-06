<?php

namespace GenericDatabase\Helpers\Parsers;

use Exception;
use Nette\Neon\Neon as Neom;

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
        } catch (Exception $_) {
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
        } catch (Exception $_) {
            return [];
        }
    }
}
