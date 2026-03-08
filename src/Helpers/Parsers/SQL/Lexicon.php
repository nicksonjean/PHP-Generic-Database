<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers\SQL;

use RuntimeException;

/**
 * Lexicon containing all SQL reserved words for the SQL language.
 * Used by Parse class for escaping and identifying reserved identifiers.
 *
 * Reads reserved words from an external .lex file format.
 * Format: One keyword per line, comments start with #, empty lines ignored.
 *
 * @package GenericDatabase\Helpers\Parsers\SQL
 */
final class Lexicon
{
    /**
     * Default lexicon file name
     */
    private const LEXICON_FILE = DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'SQL_Reserved_Words.lex';

    /**
     * Cached reserved words array
     */
    private static ?array $cachedWords = null;

    /**
     * Custom lexicon file path (optional)
     */
    private static ?string $customLexiconPath = null;

    /**
     * Set a custom lexicon file path.
     *
     * @param string $path Full path to the .lex file
     * @return void
     */
    public static function setLexiconPath(string $path): void
    {
        if (self::$customLexiconPath !== $path) {
            self::$customLexiconPath = $path;
            self::$cachedWords = null; // Invalidate cache when path changes
        }
    }

    /**
     * Get the current lexicon file path.
     *
     * @return string The resolved path to the lexicon file
     */
    public static function getLexiconPath(): string
    {
        if (self::$customLexiconPath !== null) {
            return self::$customLexiconPath;
        }

        return __DIR__ . DIRECTORY_SEPARATOR . self::LEXICON_FILE;
    }

    /**
     * Clear the cached reserved words, forcing a reload on next access.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cachedWords = null;
    }

    /**
     * Reset to default lexicon path and clear cache.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$customLexiconPath = null;
        self::$cachedWords = null;
    }

    /**
     * Get all SQL reserved words.
     *
     * Reads from the .lex file on first call, then caches the result.
     * The .lex format supports:
     * - One keyword per line
     * - Comments starting with # (ignored)
     * - Empty lines (ignored)
     * - Leading/trailing whitespace (trimmed)
     *
     * @return array<int, string> Array of reserved words in uppercase
     * @throws RuntimeException If the lexicon file cannot be read
     */
    public static function getReservedWords(): array
    {
        if (self::$cachedWords !== null) {
            return self::$cachedWords;
        }

        self::$cachedWords = self::loadFromFile(self::getLexiconPath());

        return self::$cachedWords;
    }

    /**
     * Load reserved words from a .lex file.
     *
     * @param string $filePath Path to the .lex file
     * @return array<int, string> Array of reserved words
     * @throws RuntimeException If the file cannot be read
     */
    private static function loadFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException(
                sprintf('Lexicon file not found: %s', $filePath)
            );
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException(
                sprintf('Lexicon file is not readable: %s', $filePath)
            );
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new RuntimeException(
                sprintf('Failed to read lexicon file: %s', $filePath)
            );
        }

        return self::parseLines($lines);
    }

    /**
     * Parse lines from the .lex file format.
     *
     * @param array<int, string> $lines Raw lines from the file
     * @return array<int, string> Parsed reserved words
     */
    private static function parseLines(array $lines): array
    {
        $words = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip empty lines and comments
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Handle inline comments (keyword # comment)
            $commentPos = strpos($trimmed, '#');
            if ($commentPos !== false) {
                $trimmed = trim(substr($trimmed, 0, $commentPos));
                if ($trimmed === '') {
                    continue;
                }
            }

            // Store in uppercase for consistent comparison
            $words[] = mb_strtoupper($trimmed);
        }

        return $words;
    }

    /**
     * Check if a word is a reserved SQL keyword.
     *
     * @param string $word The word to check
     * @return bool True if the word is reserved
     */
    public static function isReserved(string $word): bool
    {
        return in_array(mb_strtoupper(trim($word)), self::getReservedWords(), true);
    }

    /**
     * Get the count of reserved words.
     *
     * @return int Number of reserved words
     */
    public static function count(): int
    {
        return count(self::getReservedWords());
    }
}
