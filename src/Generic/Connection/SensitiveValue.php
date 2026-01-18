<?php

namespace GenericDatabase\Generic\Connection;

use SensitiveParameterValue;

/**
 * The SensitiveValue class is designed to conceal sensitive information by transforming it into a "sensitive" version, which can be used in logs, error messages, etc.
 *
 * Methods:
 * - `__construct(string $value)` Initializes the object with a value to be wrapped and transforms it into a sensitive version using the transformString method.
 * - `__toString(): string:` Returns the original value as a string.
 * - `__debugInfo(): array:` Returns the sensitive value as a string, which can be used for debugging purposes.
 * - `getValue(): string:` Returns the original value.
 * - `getMaskedValue(): string:` Returns the masked (sensitive) value.
 * - `transformString(string $input): string:` Replaces each character of the input string with a special character representing its type (e.g., lowercase letter, uppercase letter, digit, etc.).
 */
class SensitiveValue
{
    private string $sensitiveValue = '';
    private const LOWERCASE_LETTER = '♠';
    private const UPPERCASE_LETTER = '♥';
    private const SYMBOL = '♦';
    private const NUMBER = '♣';
    private const ANY = '●';

    /**
     * @param mixed $value The value to wrap
     */
    public function __construct(private mixed $value)
    {
        $this->sensitiveValue = $this->transformString($value);
    }

    /**
     * Returns original value as string
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }

    /**
     * Returns the sensitive value as a string
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return ['value' => $this->sensitiveValue];
    }

    /**
     * Returns the original value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the masked value
     *
     * @return mixed
     */
    public function getMaskedValue(): mixed
    {
        return $this->sensitiveValue;
    }

    /**
     * Transforms a string value into a "sensitive" version
     *
     * The method replaces each character of the input string with a
     * special character that represents its type:
     *
     * - Lowercase letter: self::LOWERCASE_LETTER
     * - Uppercase letter: self::UPPERCASE_LETTER
     * - Digit: self::NUMBER
     * - Non-alphanumeric character: self::SYMBOL
     * - Other characters are left unchanged
     *
     * This method is used to generate a "sensitive" version of a string
     * value, which can be used to conceal sensitive information in logs,
     * error messages, etc.
     *
     * @param string $input The string value to transform
     * @return string The transformed string value
     */

    public function transformString(string $input): string
    {
        return preg_replace_callback('/./u', function ($matches) {
            $char = $matches[0];
            return match (true) {
                ctype_lower($char) => self::LOWERCASE_LETTER,
                ctype_upper($char) => self::UPPERCASE_LETTER,
                ctype_digit($char) => self::NUMBER,
                !ctype_alnum($char) => self::SYMBOL,
                default => self::ANY,
            };
        }, $input);
    }
}
