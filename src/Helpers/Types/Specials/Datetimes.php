<?php

namespace GenericDatabase\Helpers\Types\Specials;

use GenericDatabase\Helpers\Types\Scalars\Strings;
use GenericDatabase\Helpers\Types\Compounds\Arrays;

/**

 * The `GenericDatabase\Helpers\Types\Specials\Datetimes` class provides regular expression
 * patterns for parsing and validating date and time strings. It also includes
 * methods for retrieving the regular expression patterns and extracting information from the input string.
 *
 * Example Usage:
 * <code>
 * //Parser a date em time on format: YYYY-DD-MM HH:mm:SS
 * $parse = Datetimes::getPattern("2023-28-02 10:45:30");
 *
 * //Parser a date em time on format: YYYY-MM-DD HH:mm
 * $parse = Datetimes::getPattern("2024-02-29 10:45");
 *
 * //Parser a date em time on format: MM-DD-YYYY HH:mm:SS zzz
 * $parse = Datetimes::getPattern("02-28-2023 10:45:30 +02:00");
 *
 * //Parser a date em time on format: DD-MM-YYYY HH:mm:SS tt
 * $parse = Datetimes::getPattern("29-02-2024 10:45:30 PM");
 *
 * //Parser a date em time on format: HH:MM:SS
 * $parse = Datetimes::getPattern("13:45:30");
 * </code>
 *
 * Main functionalities:
 * - Provides regular expression patterns for various date and time formats
 * - Parses and validates date and time strings using the regular expression patterns
 * - Retrieves the regular expression patterns and extracted information from the input string
 *
 * Methods:
 * - `getPattern(string $input):` Parses and validates the input string using the regular expression patterns and returns the extracted information.
 *
 * Fields
 * - `TIME_SEPARATOR`: Regular expression pattern for matching time separators (e.g., ':', '.').
 * - `TIMEZONE_HOURS_SEPARATOR`: Regular expression pattern for matching timezone hours separators.
 * - `TIMEZONE_MINUTES_SEPARATOR`: Regular expression pattern for matching timezone minutes separators.
 * - `TIMEZONE_SEPARATOR`: Regular expression pattern for matching timezone separators.
 * - `MERIDIEM_HOURS_SEPARATOR`: Regular expression pattern for matching meridiem hours separators.
 * - `MERIDIEM_MINUTES_SEPARATOR`: Regular expression pattern for matching meridiem minutes separators.
 * - `MERIDIEM_SEPARATOR`: Regular expression pattern for matching meridiem separators.
 * - `DIGITS`: Regular expression pattern for matching digits (0-9).
 * - `HOURS`: Regular expression pattern for matching hours (00-23).
 * - `MINUTES`: Regular expression pattern for matching minutes (00-59).
 * - `MERIDIEM_HOURS_12`: Regular expression pattern for matching 12-hour format meridiem hours (01-12).
 * - `TIMEZONE_HOURS_24`: Regular expression pattern for matching 24-hour format timezone hours (00-23).
 * - `MERIDIEM_MINUTES_12`: Regular expression pattern for matching meridiem minutes (00-59).
 * - `TIMEZONE_MINUTES_24`: Regular expression pattern for matching timezone minutes (00-59).
 * - `MERIDIEM_SECONDS_12`: Regular expression pattern for matching meridiem seconds (00-59).
 * - `TIMEZONE_SECONDS_24`: Regular expression pattern for matching timezone seconds (00-59).
 * - `MERIDIEM`: Regular expression pattern for matching meridiem (AM/PM).
 * - `TIMEZONE`: Regular expression pattern for matching timezone (Z or +/-HH:MM).
 * - `DATE_SEPARATOR`: Regular expression pattern for matching date separators (e.g., '/', '-', '|').
 * - `DATE_TIME_SEPARATOR`: Regular expression pattern for matching date-time separators (e.g., 'T', '/', '-', '|').
 * - `DAYS_28_SEPARATOR`: Regular expression pattern for matching 28 days separator.
 * - `DAYS_30_SEPARATOR`: Regular expression pattern for matching 30 days separator.
 * - `DAYS_31_SEPARATOR`: Regular expression pattern for matching 31 days separator.
 * - `MONTHS_28_SEPARATOR`: Regular expression pattern for matching 28 months separator.
 * - `MONTHS_30_SEPARATOR`: Regular expression pattern for matching 30 months separator.
 * - `MONTHS_31_SEPARATOR`: Regular expression pattern for matching 31 months separator.
 * - `LEAP_DAY_SEPARATOR`: Regular expression pattern for matching leap day separator.
 * - `LEAP_MONTH_SEPARATOR`: Regular expression pattern for matching leap month separator.
 * - `LEAP_YEAR_SEPARATOR`: Regular expression pattern for matching leap year separator.
 * - `YEAR_SEPARATOR`: Regular expression pattern for matching year separator.
 * - `DAYS_28`: Regular expression pattern for matching 28 days (01-28).
 * - `DAYS_30`: Regular expression pattern for matching 30 days (01-30).
 * - `DAYS_31`: Regular expression pattern for matching 31 days (01-31).
 * - `MONTHS_28`: Regular expression pattern for matching 28 months (02).
 * - `MONTHS_30`: Regular expression pattern for matching 30 months (01-12 except 02).
 * - `MONTHS_31`: Regular expression pattern for matching 31 months (01-12 except 02).
 * - `LEAP_DAY`: Regular expression pattern for matching leap day (29).
 * - `LEAP_MONTH`: Regular expression pattern for matching leap month (02).
 * - `LEAP_YEAR`: Regular expression pattern for matching leap year.
 * - `YEAR`: Regular expression pattern for matching year (YYYY or YY).
 *
 * @package GenericDatabase\Helpers\Types\Specials
 * @subpackage Datetimes
 */
class Datetimes
{
    private const TIME_SEPARATOR = '[:.]';
    private const TIMEZONE_HOURS_SEPARATOR = '(?<timezone_hours_separator>' . self::TIME_SEPARATOR . ')';
    private const TIMEZONE_MINUTES_SEPARATOR = '(?<timezone_minutes_separator>' . self::TIME_SEPARATOR . ')';
    private const TIMEZONE_SEPARATOR = '(?<timezone_separator>[\s])';
    private const MERIDIEM_HOURS_SEPARATOR = '(?<meridiem_hours_separator>' . self::TIME_SEPARATOR . ')';
    private const MERIDIEM_MINUTES_SEPARATOR = '(?<meridiem_minutes_separator>' . self::TIME_SEPARATOR . ')';
    private const MERIDIEM_SEPARATOR = '(?<meridiem_separator>[\s])';
    private const DIGITS = '[0-5][0-9]';
    private const HOURS = '[0-1][0-9]|2[0-3]';
    private const MINUTES = self::DIGITS;
    private const MERIDIEM_HOURS_12 = '(?<meridiem_hours_12>0[1-9]|1[0-2])';
    private const TIMEZONE_HOURS_24 = '(?<timezone_hours_24>1[0-9]|2[0-3]|0?[0-9])';
    private const MERIDIEM_MINUTES_12 = '(?<meridiem_minutes>' . self::DIGITS . ')';
    private const TIMEZONE_MINUTES_24 = '(?<timezone_minutes>' . self::DIGITS . ')';
    private const MERIDIEM_SECONDS_12 = '(?<meridiem_seconds>' . self::DIGITS . ')';
    private const TIMEZONE_SECONDS_24 = '(?<timezone_seconds>' . self::DIGITS . ')';
    private const MERIDIEM = '(?<meridiem>[AaPp][Mm])';
    private const TIMEZONE =
    '(?<timezone>Z|[+|-]' . '(?:' . self::HOURS . ')' . self::TIME_SEPARATOR . '?(?:' . self::MINUTES . ')?)?';
    private const DATE_SEPARATOR = '[\s\/\|\-]';
    private const DATE_TIME_SEPARATOR = '(?<date_time_separator>[\s\/\|\-|T])?';
    private const DAYS_28_SEPARATOR = '(?<day_28_separator>' . self::DATE_SEPARATOR . ')';
    private const DAYS_30_SEPARATOR = '(?<day_30_separator>' . self::DATE_SEPARATOR . ')';
    private const DAYS_31_SEPARATOR = '(?<day_31_separator>' . self::DATE_SEPARATOR . ')';
    private const MONTHS_28_SEPARATOR = '(?<month_28_separator>' . self::DATE_SEPARATOR . ')';
    private const MONTHS_30_SEPARATOR = '(?<month_30_separator>' . self::DATE_SEPARATOR . ')';
    private const MONTHS_31_SEPARATOR = '(?<month_31_separator>' . self::DATE_SEPARATOR . ')';
    private const LEAP_DAY_SEPARATOR = '(?<leap_day_separator>' . self::DATE_SEPARATOR . ')';
    private const LEAP_MONTH_SEPARATOR = '(?<leap_month_separator>' . self::DATE_SEPARATOR . ')';
    private const LEAP_YEAR_SEPARATOR = '(?<leap_year_separator>' . self::DATE_SEPARATOR . ')';
    private const YEAR_SEPARATOR = '(?<year_separator>' . self::DATE_SEPARATOR . ')';
    private const DAYS_28 = '(?<day_28>0[1-9]|1[0-9]|2[0-8])';
    private const DAYS_30 = '(?<day_30>0[1-9]|[1-2][0-9]|30)';
    private const DAYS_31 = '(?<day_31>0[1-9]|[1-2][0-9]|3[01])';
    private const MONTHS_28 = '(?<month_28>02)';
    private const MONTHS_30 = '(?<month_30>0[13456789]|1[012])';
    private const MONTHS_31 = '(?<month_31>0[13578]|1[02])';
    private const LEAP_DAY = '(?<leap_day>29)';
    private const LEAP_MONTH = '(?<leap_month>02)';
    private const LEAP_YEAR =
    '(?<leap_year>(?:\d{2}(?:0[48]|[2468][048]|[13579][26]))|(?:(?:[02468][048])|[13579][26])00)';
    private const YEAR = '(?<year>(?:\d{4}|(?:(?:0[48]|[2468][048]|[13579][26]))))';
    private static array $separators = [
        'dateSeparators' => [
            'day_28_separator',
            'day_30_separator',
            'day_31_separator',
            'month_28_separator',
            'month_30_separator',
            'month_31_separator',
            'leap_day_separator',
            'leap_month_separator',
            'leap_year_separator',
            'year_separator'
        ],
        'timeSeparators' => [
            'timezone_hours_separator',
            'timezone_minutes_separator',
            'timezone_seconds',
            'meridiem_hours_separator',
            'meridiem_minutes_separator',
            'meridiem_seconds'
        ],
        'dateTimeSeparators' => ['date_time_separator'],
        'meridiemSeparators' => ['meridiem_separator'],
        'timezoneSeparators' => ['timezone_separator']
    ];

    /**
     * @var mixed Instance of date and time regular expression patterns.
     */
    private static mixed $pattern;

    /**
     * @var array Load date and time regular expression patterns from JSON file.
     */
    private static array $patternMap = [
        'ydm' => 'regexYDMHMS',
        'ymd' => 'regexYMDHMS',
        'mdy' => 'regexMDYHMS',
        'dmy' => 'regexDMYHMS',
        'hms' => 'regexHMS'
    ];

    /**
     * Get the regular expression pattern.
     *
     * @param string $regex The regular expression.
     * @param int $init The start position.
     * @param int $term The end position.
     * @return string The regular expression pattern.
     */
    private static function getRegex(string $regex, int $init = 1, int $term = 0): string
    {
        return substr($regex, $init - 1, strlen($regex) - $term);
    }

    /**
     * Get the time regular expression pattern.
     *
     * @return string The time regular expression pattern.
     */
    private static function regexHMSM(): string
    {
        return '^(?:' . self::MERIDIEM_HOURS_12 . self::MERIDIEM_HOURS_SEPARATOR . self::MERIDIEM_MINUTES_12 .
            self::MERIDIEM_MINUTES_SEPARATOR . '?' . self::MERIDIEM_SECONDS_12 . '?' . self::MERIDIEM_SEPARATOR . '?' .
            self::MERIDIEM . ')$';
    }

    /**
     * Get the time regular expression pattern.
     *
     * @return string The time regular expression pattern.
     */
    private static function regexHMSZ(): string
    {
        return '^(?:' . self::TIMEZONE_HOURS_24 . self::TIMEZONE_HOURS_SEPARATOR . self::TIMEZONE_MINUTES_24 .
            self::TIMEZONE_MINUTES_SEPARATOR . '?' . self::TIMEZONE_SECONDS_24 . '?' . self::TIMEZONE_SEPARATOR . '?' .
            self::TIMEZONE . ')$';
    }

    /**
     * Get the time regular expression pattern.
     *
     * @return string The time regular expression pattern.
     */
    private static function regexHMS(): string
    {
        return '^(?:' . self::getRegex(self::regexHMSM(), 2, 2) . '|' . self::getRegex(self::regexHMSZ(), 2, 2) . '?)$';
    }

    /**
     * Get the date regular expression pattern.
     *
     * @return string The date regular expression pattern.
     */
    private static function regexYDM(): string
    {
        return '^(?:' . self::YEAR . self::YEAR_SEPARATOR . '?' . '(?:(?:(?:' . self::DAYS_31 .
            self::DAYS_31_SEPARATOR . '?' . self::MONTHS_31 . ')|(?:' . self::DAYS_30 . self::DAYS_30_SEPARATOR . '?' .
            self::MONTHS_30 . ')|(?:' . self::DAYS_28 . self::DAYS_28_SEPARATOR . '?' . self::MONTHS_28 . ')))|(?:' .
            self::LEAP_YEAR . self::LEAP_YEAR_SEPARATOR . '?' . self::LEAP_DAY . self::LEAP_DAY_SEPARATOR . '?' .
            self::LEAP_MONTH . '))$';
    }

    /**
     * Get the date regular expression pattern.
     *
     * @return string The date regular expression pattern.
     */
    private static function regexYMD(): string
    {
        return '^(?:' . self::YEAR . self::YEAR_SEPARATOR . '?' . '(?:(?:(?:' . self::MONTHS_31 .
            self::MONTHS_31_SEPARATOR . '?' . self::DAYS_31 . ')|(?:' . self::MONTHS_30 . self::MONTHS_30_SEPARATOR .
            '?' . self::DAYS_30 . ')|(?:' . self::MONTHS_28 . self::MONTHS_28_SEPARATOR . '?' . self::DAYS_28 .
            ')))|(?:' . self::LEAP_YEAR . self::LEAP_YEAR_SEPARATOR . '?' . self::LEAP_MONTH .
            self::LEAP_MONTH_SEPARATOR . '?' . self::LEAP_DAY . '))$';
    }

    /**
     * Get the date regular expression pattern.
     *
     * @return string The date regular expression pattern.
     */
    private static function regexMDY(): string
    {
        return '^(?:(?:(?:' . self::MONTHS_31 . self::MONTHS_31_SEPARATOR . '?' . self::DAYS_31 . ')|(?:' .
            self::MONTHS_30 . self::MONTHS_30_SEPARATOR . '?' . self::DAYS_30 . ')|(?:' . self::MONTHS_28 .
            self::MONTHS_28_SEPARATOR . '?' . self::DAYS_28 . '))' . self::DAYS_28_SEPARATOR . '?' . self::YEAR .
            '|(?:(?:' . self::LEAP_MONTH . self::LEAP_MONTH_SEPARATOR . '?' . self::LEAP_DAY .
            self::LEAP_DAY_SEPARATOR . '?' . self::LEAP_YEAR . ')))$';
    }

    /**
     * Get the date regular expression pattern.
     *
     * @return string The date regular expression pattern.
     */
    private static function regexDMY(): string
    {
        return '^(?:(?:(?:' . self::DAYS_31 . self::DAYS_31_SEPARATOR . '?' . self::MONTHS_31 . ')|(?:' .
            self::DAYS_30 . self::DAYS_30_SEPARATOR . '?' . self::MONTHS_30 . ')|(?:' . self::DAYS_28 .
            self::DAYS_28_SEPARATOR . '?' . self::MONTHS_28 . '))' . self::MONTHS_28_SEPARATOR . '?' . self::YEAR .
            '|(?:(?:' . self::LEAP_DAY . self::LEAP_DAY_SEPARATOR . '?' . self::LEAP_MONTH .
            self::LEAP_MONTH_SEPARATOR . '?' . self::LEAP_YEAR . ')))$';
    }

    /**
     * Get the date and time regular expression pattern.
     *
     * @return string The date and time regular expression pattern.
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function regexYDMHMS(): string
    {
        return '^(?:' .
            self::getRegex(self::regexYDM(), 2, 2) .
            self::DATE_TIME_SEPARATOR .
            self::getRegex(self::regexHMS(), 2, 2) .
            ')$';
    }

    /**
     * Get the date and time regular expression pattern.
     *
     * @return string The date and time regular expression pattern.
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function regexYMDHMS(): string
    {
        return '^(?:' .
            self::getRegex(self::regexYMD(), 2, 2) .
            self::DATE_TIME_SEPARATOR .
            self::getRegex(self::regexHMS(), 2, 2) .
            ')$';
    }

    /**
     * Get the date and time regular expression pattern.
     *
     * @return string The date and time regular expression pattern.
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function regexMDYHMS(): string
    {
        return '^(?:' .
            self::getRegex(self::regexMDY(), 2, 2) .
            self::DATE_TIME_SEPARATOR .
            self::getRegex(self::regexHMS(), 2, 2) .
            ')$';
    }

    /**
     * Get the date and time regular expression pattern.
     *
     * @return string The date and time regular expression pattern.
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function regexDMYHMS(): string
    {
        return '^(?:' .
            self::getRegex(self::regexDMY(), 2, 2) .
            self::DATE_TIME_SEPARATOR .
            self::getRegex(self::regexHMS(), 2, 2) .
            ')$';
    }

    /**
     * Returns an array of regular expressions used for pattern matching.
     *
     * @return array
     */
    private static function getRegexp(): array
    {
        return array_combine(
            array_keys(self::$patternMap),
            array_map(fn($method) => '/' . self::$method() . '/', array_values(self::$patternMap))
        );
    }

    /**
     * Returns the separators from the given indexes in the string array.
     *
     * @param array $indexes
     * @param array $string
     * @return string|array|null
     */
    private static function getSeparators(array $indexes, array $string): string|array|null
    {
        foreach ($indexes as $index) {
            if (array_key_exists($index, $string)) {
                return $string[$index];
            }
        }
        return null;
    }

    /**
     * Sets the separators from the input data array.
     *
     * @param array $inputData
     * @return array
     */
    private static function setSeparators(array $inputData): array
    {
        $flatInputData = Arrays::arrayFlatten($inputData);
        return [
            'flatInputData' => $flatInputData,
            'format' => self::getSeparators(['format'], $flatInputData),
            'dsp' => self::getSeparators(['date_separator'], $flatInputData),
            'tsp' => self::getSeparators(['time_separator'], $flatInputData),
            'dtsp' => self::getSeparators(['date_time_separator'], $flatInputData),
            'msp' => self::getSeparators(['meridiem_separator'], $flatInputData),
            'tzsp' => self::getSeparators(['timezone_separator'], $flatInputData),
        ];
    }

    /**
     * Sets the mask based on the input data array.
     *
     * @param array $inputData
     * @return string
     */
    private static function setMask(array $inputData): string
    {
        $input = array_key_first($inputData);
        [
            'format' => $format,
            'dsp' => $dsp,
            'tsp' => $tsp,
            'dtsp' => $dtsp,
            'msp' => $msp,
            'tzsp' => $tzsp
        ] = self::setSeparators($inputData);
        return self::buildMask($input, $format, $dsp, $tsp, $dtsp, $msp, $tzsp);
    }

    /**
     *  Builds the mask based on the input, format, and separators.
     *
     * @param string $input
     * @param string $format
     * @param string|null $dsp
     * @param string|null $tsp
     * @param string|null $dtsp
     * @param string|null $msp
     * @param string|null $tzsp
     * @return string
     */
    private static function buildMask(
        string $input,
        string $format,
        ?string $dsp,
        ?string $tsp,
        ?string $dtsp,
        ?string $msp,
        ?string $tzsp
    ): string {
        $length = strlen($input);
        return match (true) {
            $length === 5 && !$dtsp && !$msp && !$tzsp => self::buildTimeMask($format, $tsp),
            $length === 8 && !$dtsp && $msp && !$tzsp => self::buildTimeWithMeridiemMask($format, $tsp, $msp),
            $length === 12 && !$dtsp && !$msp && $tzsp => self::buildTimeWithTimespanMask($format, $tsp, $tzsp),
            $length === 8 && !$dtsp && !$msp && !$tzsp => self::buildTimeWithSecondsMask($format, $tsp),
            $length === 11 && !$dtsp && $msp && !$tzsp => self::buildTimeWithSecondsAndMeridiemMask(
                $format,
                $tsp,
                $msp
            ),
            $length === 15 && !$dtsp && !$msp && $tzsp => self::buildTimeWithSecondsAndTimespanMask(
                $format,
                $tsp,
                $tzsp
            ),
            $length === 10 && !$dtsp && !$msp && !$tzsp => self::buildDateMask($format, $dsp),
            $length === 16 && $dtsp && !$msp && !$tzsp => self::buildDateTimeMask($format, $dsp, $tsp, $dtsp),
            $length === 19 && $dtsp && $msp && !$tzsp => self::buildDateTimeWithMeridiemMask(
                $format,
                $dsp,
                $tsp,
                $dtsp,
                $msp
            ),
            $length === 19 && $dtsp && !$msp && !$tzsp => self::buildDateTimeWithSecondsMask(
                $format,
                $dsp,
                $tsp,
                $dtsp
            ),
            $length === 23 && $dtsp && !$msp && $tzsp => self::buildDateTimeWithTimespanMask(
                $format,
                $dsp,
                $tsp,
                $dtsp,
                $tzsp
            ),
            $length === 22 && $dtsp && $msp && !$tzsp => self::buildDateTimeWithSecondsAndMeridiemMask(
                $format,
                $dsp,
                $tsp,
                $dtsp,
                $msp
            ),
            $length === 26 && $dtsp && !$msp && $tzsp => self::buildDateTimeWithSecondsAndTimespanMask(
                $format,
                $dsp,
                $tsp,
                $dtsp,
                $tzsp
            ),
            default => '',
        };
    }

    /**
     * Builds the time mask based on the format and time separator.
     *
     * @param string $format
     * @param string $tsp
     * @return string
     */
    private static function buildTimeMask(string $format, string $tsp): string
    {
        return match ($format) {
            'hms' => "H{$tsp}i",
            default => '',
        };
    }

    /**
     * Builds the time mask with meridiem based on the format, time separator, and meridiem separator.
     *
     * @param string $format
     * @param string $tsp
     * @param string|null $msp
     * @return string
     */
    private static function buildTimeWithMeridiemMask(string $format, string $tsp, ?string $msp): string
    {
        return match ($format) {
            'hms' => "H{$tsp}i{$msp}A",
            default => '',
        };
    }

    /**
     * Builds the time mask with timespan based on the format, time separator, and timespan separator.
     *
     * @param string $format
     * @param string $tsp
     * @param string $tzsp
     * @return string
     */
    private static function buildTimeWithTimespanMask(string $format, string $tsp, string $tzsp): string
    {
        return match ($format) {
            'hms' => "H{$tsp}i{$tzsp}P",
            default => '',
        };
    }

    /**
     * Builds the time mask with seconds based on the format and time separator.
     *
     * @param string $format
     * @param string $tsp
     * @return string
     */
    private static function buildTimeWithSecondsMask(string $format, string $tsp): string
    {
        return match ($format) {
            'hms' => "H{$tsp}i{$tsp}s",
            default => '',
        };
    }

    /**
     * Builds the time mask with seconds and meridiem based on the format, time separator, and meridiem separator.
     *
     * @param string $format
     * @param string $tsp
     * @param string|null $msp
     * @return string
     */
    private static function buildTimeWithSecondsAndMeridiemMask(string $format, string $tsp, ?string $msp): string
    {
        return match ($format) {
            'hms' => "H{$tsp}i{$tsp}s{$msp}A",
            default => '',
        };
    }

    /**
     * Builds the time mask with seconds and timespan based on the format, time separator, and timespan separator.
     *
     * @param string $format
     * @param string $tsp
     * @param string $tzsp
     * @return string
     */
    private static function buildTimeWithSecondsAndTimespanMask(string $format, string $tsp, string $tzsp): string
    {
        return match ($format) {
            'hms' => "H{$tsp}i{$tsp}s{$tzsp}P",
            default => '',
        };
    }

    /**
     * Builds the date mask based on the format and date separator.
     *
     * @param string $format
     * @param string $dsp
     * @return string
     */
    private static function buildDateMask(string $format, string $dsp): string
    {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d",
            'ydm' => "Y{$dsp}d{$dsp}m",
            'dmy' => "d{$dsp}m{$dsp}Y",
            'mdy' => "m{$dsp}d{$dsp}Y",
            default => '',
        };
    }

    /**
     * Builds the date-time mask based on the format, date separator, time separator, and date-time separator.
     *
     * @param string $format
     * @param string $dsp
     * @param string $tsp
     * @param string $dtsp
     * @return string
     */
    private static function buildDateTimeMask(string $format, string $dsp, string $tsp, string $dtsp): string
    {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d{$dtsp}H{$tsp}i",
            'ydm' => "Y{$dsp}d{$dsp}m{$dtsp}H{$tsp}i",
            'dmy' => "d{$dsp}m{$dsp}Y{$dtsp}H{$tsp}i",
            'mdy' => "m{$dsp}d{$dsp}Y{$dtsp}H{$tsp}i",
            default => '',
        };
    }

    /**
     * Builds the date-time mask with seconds and meridiem based on the format, date separator, time separator,
     * date-time separator, and meridiem separator.
     *
     * @param string $format
     * @param string $dsp
     * @param string $tsp
     * @param string $dtsp
     * @param string|null $msp
     * @return string
     */
    private static function buildDateTimeWithMeridiemMask(
        string $format,
        string $dsp,
        string $tsp,
        string $dtsp,
        ?string $msp
    ): string {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d{$dtsp}H{$tsp}i{$msp}A",
            'ydm' => "Y{$dsp}d{$dsp}m{$dtsp}H{$tsp}i{$msp}A",
            'dmy' => "d{$dsp}m{$dsp}Y{$dtsp}H{$tsp}i{$msp}A",
            'mdy' => "m{$dsp}d{$dsp}Y{$dtsp}H{$tsp}i{$msp}A",
            default => '',
        };
    }

    /**
     * Builds the date-time mask with seconds, meridiem based on the format, date separator, time separator,
     * date-time separator, and meridiem separator.
     *
     * @param string $format
     * @param string $dsp
     * @param string $tsp
     * @param string $dtsp
     * @param string|null $msp
     * @return string
     */
    private static function buildDateTimeWithSecondsAndMeridiemMask(
        string $format,
        string $dsp,
        string $tsp,
        string $dtsp,
        ?string $msp
    ): string {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d{$dtsp}H{$tsp}i{$tsp}s{$msp}A",
            'ydm' => "Y{$dsp}d{$dsp}m{$dtsp}H{$tsp}i{$tsp}s{$msp}A",
            'dmy' => "d{$dsp}m{$dsp}Y{$dtsp}H{$tsp}i{$tsp}s{$msp}A",
            'mdy' => "m{$dsp}d{$dsp}Y{$dtsp}H{$tsp}i{$tsp}s{$msp}A",
            default => '',
        };
    }

    /**
     * Builds the date-time mask with seconds based on the format, date separator, time separator, and date-time
     * separator.
     *
     * @param string $format
     * @param string $dsp
     * @param string $tsp
     * @param string $dtsp
     * @return string
     */
    private static function buildDateTimeWithSecondsMask(string $format, string $dsp, string $tsp, string $dtsp): string
    {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d{$dtsp}H{$tsp}i{$tsp}s",
            'ydm' => "Y{$dsp}d{$dsp}m{$dtsp}H{$tsp}i{$tsp}s",
            'dmy' => "d{$dsp}m{$dsp}Y{$dtsp}H{$tsp}i{$tsp}s",
            'mdy' => "m{$dsp}d{$dsp}Y{$dtsp}H{$tsp}i{$tsp}s",
            default => '',
        };
    }

    /**
     * Builds the date-time mask with timespan based on the format, date separator, time separator, date-time
     * separator, and timespan separator.
     *
     * @param string $format
     * @param string $dsp
     * @param string $tsp
     * @param string $dtsp
     * @param string $tzsp
     * @return string
     */
    private static function buildDateTimeWithTimespanMask(
        string $format,
        string $dsp,
        string $tsp,
        string $dtsp,
        string $tzsp
    ): string {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d{$dtsp}H{$tsp}i{$tzsp}P",
            'ydm' => "Y{$dsp}d{$dsp}m{$dtsp}H{$tsp}i{$tzsp}P",
            'dmy' => "d{$dsp}m{$dsp}Y{$dtsp}H{$tsp}i{$tzsp}P",
            'mdy' => "m{$dsp}d{$dsp}Y{$dtsp}H{$tsp}i{$tzsp}P",
            default => '',
        };
    }

    /**
     * Builds the date-time mask with seconds and timespan based on the format, date separator, time separator,
     * date-time separator, and timespan separator.
     *
     * @param string $format
     * @param string $dsp
     * @param string $tsp
     * @param string $dtsp
     * @param string|null $tzsp
     * @return string
     */
    private static function buildDateTimeWithSecondsAndTimespanMask(
        string $format,
        string $dsp,
        string $tsp,
        string $dtsp,
        ?string $tzsp
    ): string {
        return match ($format) {
            'ymd' => "Y{$dsp}m{$dsp}d{$dtsp}H{$tsp}i{$tsp}s{$tzsp}P",
            'ydm' => "Y{$dsp}d{$dsp}m{$dtsp}H{$tsp}i{$tsp}s{$tzsp}P",
            'dmy' => "d{$dsp}m{$dsp}Y{$dtsp}H{$tsp}i{$tsp}s{$tzsp}P",
            'mdy' => "m{$dsp}d{$dsp}Y{$dtsp}H{$tsp}i{$tsp}s{$tzsp}P",
            default => '',
        };
    }

    /**
     * Loads the mask file based on the input data array.
     *
     * @param array $inputData
     * @return array
     */
    private static function loadMaskFile(array $inputData): array
    {
        [
            'flatInputData' => $flatInputData,
            'dsp' => $dsp,
            'tsp' => $tsp,
            'dtsp' => $dtsp,
            'msp' => $msp,
            'tzsp' => $tzsp
        ] = self::setSeparators($inputData);
        $json = __DIR__ . DIRECTORY_SEPARATOR . 'Specials' . DIRECTORY_SEPARATOR . 'Datetimes' . DIRECTORY_SEPARATOR . $flatInputData['format'] . '.json';
        self::$pattern = json_decode(
            str_replace(
                ['~DSP~', '~TSP~', '~DTSP~', '~MSP~', '~TZSP~'],
                [$dsp, $tsp, $dtsp, $msp, $tzsp],
                file_get_contents($json)
            )
        );
        return self::$pattern;
    }

    /**
     * Gets the mask based on the input data array.
     *
     * @param array $inputData
     * @return array
     */
    private static function getMask(array $inputData): array
    {
        $input = array_key_first($inputData);
        $result = [
            'php_mask' => '',
            'iso_mask' => '',
            'warnings' => [],
        ];
        $masks = self::loadMaskFile($inputData);
        $format = self::setMask($inputData);
        foreach ($masks as $mask) {
            if ($format === $mask[0]) {
                $parsed = date_parse_from_format($mask[0], $input);
                if ($parsed['error_count'] === 0) {
                    $result['php_mask'] = $mask[0];
                    $result['iso_mask'] = $mask[1];
                    $result += array_diff_key($parsed, array_flip(['warning_count', 'error_count']));
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Fetches the separators from the result array based on the input and separator types.
     *
     * @param array $result
     * @param string $input
     * @return void
     */
    private static function fetchSeparators(array &$result, string $input): void
    {
        $separatorTypes = ['date', 'date_time', 'time', 'meridiem', 'timezone'];
        foreach ($separatorTypes as $separatorType) {
            $separatorList = self::$separators[Strings::toCamelize($separatorType) . 'Separators'];
            self::$separators[$separatorType] = self::getSeparators($separatorList, $result[$input]);
            if (!empty(self::$separators[$separatorType])) {
                $result[$input][$separatorType . '_separator'] = self::$separators[$separatorType];
            }
        }
    }

    /**
     * Gets the pattern based on the input.
     *
     * @param string $input
     * @return array
     */
    public static function getPattern(string $input): array
    {
        $result = [];
        foreach (self::getRegexp() as $mask => $regex) {
            if (preg_match($regex, $input, $matches)) {
                $result[$input] = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key) && !empty($value)) {
                        $result[$input][$key] = $value;
                    }
                }
                $result[$input]['format'] = $mask;
                self::fetchSeparators($result, $input);
            }
        }
        $result[$input]['parsed'] = self::getMask($result);
        return $result;
    }
}
