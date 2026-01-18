<?php

namespace GenericDatabase\Engine\ODBC\Connection;

use ReflectionException;
use GenericDatabase\Helpers\Parsers\INI;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Engine\ODBC\Connection\DSN\DSNHandler;

class ODBC
{
    /**
     * Connection attribute to set the connection timeout.
     */
    public const ATTR_CONNECT_TIMEOUT = 1001;

    /**
     * Connection attribute to set the default fetch mode.
     */
    public const ATTR_DEFAULT_FETCH_MODE = 1100;

    /**
     * Connection attribute to set auto-commit mode.
     */
    public const ATTR_AUTOCOMMIT = 1010;

    /**
     * Connection attribute to set persistence of the connection.
     */
    public const ATTR_PERSISTENT = 13;

    /**
     * Connection attribute to set the default fetch mode.
     */
    public const ATTR_ALIAS_TYPE = 1101;

    /**
     * Connection attribute to set the default report mode.
     */
    public const ATTR_REPORT = 1110;

    /**
     * Connection attribute to set cursor.
     */
    public const ATTR_SQL_CUR_USE = 1011;

    /**
     * Turns reporting off
     */
    public const REPORT_OFF = 0;

    /**
     * Report errors from mysqli function calls
     */
    public const REPORT_ERROR = 1;

    /**
     * Throw exception for errors instead of warnings
     */
    public const REPORT_STRICT = 2;

    /**
     * Report if no index or bad index was used in a query
     */
    public const REPORT_INDEX = 4;

    /**
     * Report all errors
     */
    public const REPORT_ALL = 255;

    /**
     * Fetch mode that starts fetching rows only when they are requested.
     */
    public const FETCH_LAZY = 1;

    /**
     * Constant for the fetch mode representing fetching as an associative array
     */
    public const FETCH_ASSOC = 2;

    /**
     * Constant for the fetch mode representing fetching as a numeric array
     */
    public const FETCH_NUM = 3;

    /**
     * Constant for the fetch mode representing fetching as both a numeric and associative array
     */
    public const FETCH_BOTH = 4;

    /**
     * Constant for the fetch mode representing fetching as an object
     */
    public const FETCH_OBJ = 5;

    /**
     * Fetch mode that requires explicit binding of PHP variables to fetch values.
     */
    public const FETCH_BOUND = 6;

    /**
     * Constant for the fetch mode representing fetching a single column
     */
    public const FETCH_COLUMN = 7;

    /**
     * Constant for the fetch mode representing fetching into a new instance of a specified class
     */
    public const FETCH_CLASS = 8;

    /**
     * Constant for the fetch mode representing fetching into an existing object
     */
    public const FETCH_INTO = 9;

    /**
     * Path to the ODBC cmdlet on Windows.
     */
    private const WIN_CMDLET = '\bin\odbcinst.exe';

    /**
     * Path to the ODBC cmdlet on Linux.
     */
    private const LINUX_CMDLET = 'odbcinst -j';

    /**
     * Path to the ODBC cmdlet on Linux.
     */
    public const SQL_CUR_USE_IF_NEEDED = 0;

    /**
     * Path to the ODBC cmdlet on Linux.
     */
    public const SQL_CUR_USE_ODBC = 1;

    /**
     * Path to the ODBC cmdlet on Linux.
     */
    public const SQL_CUR_USE_DRIVER = 2;

    /**
     * Array containing the available aliases.
     *
     * @var array
     */
    private static array $availableAliases;

    /**
     * Array containing the mapped drivers.
     *
     * @var array
     */
    private static array $mapperAliases;

    /**
     * Array of drivers to be excluded.
     *
     * @var array
     */
    private static array $exceptDrivers = ['ODBC Core', 'ODBC Drivers', 'ODBC Translators', 'Administrator', 'ODBC'];

    /**
     * Array of data attributes.
     *
     * @var array
     */
    private static array $dataAttribute = [];

    /**
     * Retrieves the value of a specific attribute by name or constant.
     *
     * @param mixed $name The name of the attribute or the constant associated with it.
     * @return mixed The value of the attribute if found; null otherwise.
     * @throws ReflectionException If the reflection class cannot find the constant name.
     */
    public static function getAttribute(mixed $name): mixed
    {
        if (isset(self::$dataAttribute[$name])) {
            if (is_int($name)) {
                $result = self::$dataAttribute[Reflections::getClassConstantName(self::class, $name)];
            } else {
                $result = self::$dataAttribute[$name];
            }
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * Sets the value of a specific attribute by name or constant.
     *
     * @param mixed $name The name of the attribute or the constant associated with it. If null, the value is appended.
     * @param mixed $value The value to set for the specified attribute.
     * @return void
     * @throws ReflectionException If the reflection class cannot find the constant name.
     */
    public static function setAttribute(mixed $name, mixed $value): void
    {
        if (is_null($name)) {
            self::$dataAttribute[] = $value;
        } elseif (is_int($name)) {
            self::$dataAttribute[Reflections::getClassConstantName(self::class, $name)] = $value;
        } else {
            self::$dataAttribute[$name] = $value;
        }
    }

    /**
     * Sets the type of the input variable based on its value.
     *
     * @param mixed $input the input variable to be typed
     * @return string|int|bool|float the typed input variable
     */
    public static function setType(mixed $input): string|int|bool|float
    {
        return match (true) {
            preg_match('/\b\d+\.0+\b/', $input) => (int) $input,
            preg_match('/\b\d+\.\d*[1-9]+\b/', $input) => (float) $input,
            preg_match('/\b\d+\b/', $input) => (int) $input,
            preg_match('/true|false/i', $input) => (bool) filter_var($input, FILTER_VALIDATE_BOOLEAN),
            preg_match('/\D+/', $input) => (string) $input,
            default => (string) $input,
        };
    }

    /**
     * fetchArray function description.
     *
     * @param mixed $res
     * @return false|array
     */
    public static function fetchArray(mixed $res): false|array
    {
        if (!odbc_fetch_row($res)) {
            return false;
        }
        $row = [];
        $subfields = odbc_num_fields($res);
        for ($i = 1; $i <= $subfields; $i++) {
            $result = odbc_result($res, $i);
            if (mb_detect_encoding($result, 'utf8', true) === false) {
                $resultFixed = self::setType(mb_convert_encoding($result, 'utf8', 'ISO-8859-1'));
                $row[odbc_field_name($res, $i)] = $row[$i - 1] = $resultFixed;
            } else {
                $row[odbc_field_name($res, $i)] = $row[$i - 1] = self::setType($result);
            }
        }
        return $row;
    }

    /**
     * Fetches all the rows from the given result set.
     *
     * @param mixed $res The result set to fetch rows from.
     * @return false|array The array of rows fetched from the result set.
     * @noinspection PhpUnused
     */
    public static function fetchAll(mixed $res): false|array
    {
        $rows = [];
        while ($row = self::fetchArray($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Fetches the columns from the given result set and optionally groups them by table name.
     *
     * @param mixed $resource The result set to fetch columns from.
     * @param string|null $tableName Optional. The table name to filter the columns by.
     * @return array The columns fetched, optionally grouped by table name.
     * @noinspection PhpUnused
     */
    public static function fetchColumns(mixed $resource, ?string $tableName = null): array
    {
        $result = odbc_columns($resource);
        $columns = [];
        while ($row = odbc_fetch_array($result)) {
            $columns[] = $row;
        }
        $result = Arrays::arrayGroupBy($columns, 'TABLE_NAME');
        return ($tableName === null) ? $result : $result[$tableName];
    }

    /**
     * Fetches tables from the given database resource and returns an array of table names.
     *
     * @param mixed $resource The database resource.
     * @return array An array of table names.
     * @noinspection PhpUnused
     */
    public static function fetchTables(mixed $resource): array
    {
        $result = odbc_tables($resource);
        $tables = [];
        while (odbc_fetch_row($result)) {
            if (odbc_result($result, "TABLE_TYPE") || odbc_result($result, "SYSTEM TABLE")) {
                $tableName = odbc_result($result, "TABLE_NAME");
                if (!str_starts_with($tableName, "MSys")) {
                    $tables[] = $tableName;
                }
            }
        }
        return $tables;
    }

    /**
     * Retrieves the mapper drivers for the ODBC connection.
     *
     * @return array|string The mapper drivers.
     */
    public static function getMapperAliases(): array|string
    {
        self::$mapperAliases = DSNHandler::load();
        return self::$mapperAliases;
    }

    /**
     * Retrieves the driver settings for the ODBC connection based on the operating system.
     *
     * @return array The driver settings.
     */
    private static function getWindowsDriverSettings(): array
    {
        $command = sprintf('%s', dirname(__DIR__, 4) . self::WIN_CMDLET);
        return json_decode(shell_exec($command), true);
    }

    /**
     * Retrieves the driver settings for a specific driver based on the operating system.
     *
     * @return array The driver settings for the specified driver.
     */
    private static function getLinuxDriverSettings(): array
    {
        $command = sprintf('%s', self::LINUX_CMDLET);
        $string = shell_exec($command);
        $result = [];
        $odbcRegex = '/^([^:]+?)\s+(\d+(\.\d+)+)$/m';
        preg_match($odbcRegex, $string, $odbcMatch);
        if (!empty($odbcMatch)) {
            $result[$odbcMatch[1]] = $odbcMatch[2];
        }

        $pairRegex = '/^(.+?)\s*:\s*(.+)$/m';
        preg_match_all($pairRegex, $string, $pairMatches, PREG_SET_ORDER);
        foreach ($pairMatches as $pairMatch) {
            $key = mb_strtoupper(str_replace([' ', '.'], ['_', ''], $pairMatch[1]));
            $value = trim($pairMatch[2]);
            $result[$key] = $value;
        }
        $output = INI::parseIniFile($result['DRIVERS']);
        return array_diff_key($output, array_flip(self::$exceptDrivers));
    }

    /**
     * Retrieves the available drivers on the current system, excluding specified ODBC drivers.
     *
     * @return array Available drivers excluding specified ODBC drivers.
     * @noinspection PhpUnused
     */
    public static function getDriverSettings(): array
    {
        if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return self::getWindowsDriverSettings();
        } else {
            return self::getLinuxDriverSettings();
        }
    }

    /**
     * Retrieves the alias for a specific driver and type.
     *
     * @param string $driver The name of the driver.
     * @return array The alias for the specified driver and type.
     */
    private static function getWindowsDriverSettingsByDriver(string $driver): array
    {
        $driverSettings = self::getWindowsDriverSettings();
        return $driverSettings[$driver];
    }

    /**
     * Retrieves the alias for a specific driver and type.
     *
     * @param string $driver The name of the driver.
     * @return array The alias for the specified driver and type.
     */
    private static function getLinuxDriverSettingsByDriver(string $driver): array
    {
        $driverSettings = self::getLinuxDriverSettings();
        return $driverSettings[$driver];
    }

    /**
     * Retrieves the driver settings for a specific driver, based on the operating system.
     * This method checks the operating system and calls the appropriate method to get the driver settings.
     *
     * @param string $driver The name of the driver for which settings are to be retrieved.
     * @return array An array of driver settings for the specified driver.
     */
    public static function getDriverSettingsByDriver(string $driver): array
    {
        return mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? self::getWindowsDriverSettingsByDriver($driver)
            : self::getLinuxDriverSettingsByDriver($driver);
    }

    /**
     * Retrieves a list of available ODBC drivers on a Windows system.
     * This method executes a command to get the list of drivers and decodes the JSON output.
     *
     * @return array An array of available ODBC drivers on a Windows system.
     */
    private static function getWindowsAvailableAliases(): array
    {
        $command = sprintf('%s %s', dirname(__DIR__, 4) . self::WIN_CMDLET, "-j");
        return json_decode(shell_exec($command), true);
    }

    /**
     * Retrieves a list of available ODBC drivers on a Linux system.
     * This method calls `getLinuxDriverSettings` to get the driver settings and then extracts the keys,
     * which represent the available drivers.
     *
     * @return array An array of available ODBC drivers on a Linux system.
     */
    private static function getLinuxAvailableAliases(): array
    {
        return array_keys(self::getLinuxDriverSettings());
    }

    /**
     * Retrieves the available drivers on the current system, excluding the specified ODBC drivers.
     *
     * @return array Available drivers excluding specified ODBC drivers
     */
    public static function getAvailableAliases(): array
    {
        return mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? self::getWindowsAvailableAliases()
            : self::getLinuxAvailableAliases();
    }

    /**
     * Retrieves the available drivers on the current system, excluding the specified ODBC drivers.
     *
     * @return array Available drivers excluding specified ODBC drivers
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public static function getAvailableDrivers(): array
    {
        $availableAliases = self::getAvailableAliases();
        $mapperDrivers = self::getMapperAliases();
        $matchedDrivers = [];
        foreach ($availableAliases as $availableAlias) {
            $matchedDrivers[] = array_values(array_filter(array_map(function ($item) use ($availableAlias) {
                if (preg_match($item['alias'], $availableAlias)) {
                    return $item['driver'];
                }
            }, $mapperDrivers)));
        }
        return array_values(array_unique(array_merge(...$matchedDrivers)));
    }

    /**
     * Match aliases by driver.
     *
     * @param mixed $mapperAliases Reference to the mapper aliases array.
     * @param mixed &$defaultFlag Reference to the default alias.
     * @param mixed $availableAlias Reference to the available alias.
     * @param mixed &$matchedAlias Reference to the matched alias array.
     * @param string $driver The driver to match.
     * @param string|null $type Optional. The type of alias.
     *
     * @return array  The matched drivers array.
     */
    private static function matchAliasByDriver(
        mixed $mapperAliases,
        mixed &$defaultFlag,
        mixed $availableAlias,
        mixed &$matchedAlias,
        string $driver,
        ?string $type = null
    ): array {
        foreach ($mapperAliases as $mapperDriver) {
            $aliasRegex = $mapperDriver['alias'];
            if (preg_match($aliasRegex, $availableAlias) && $mapperDriver['driver'] === $driver) {
                if ($type === null) {
                    $defaultFlag = $availableAlias;
                    if (isset($mapperDriver['default']) && $mapperDriver['default']) {
                        $matchedAlias[] = $availableAlias;
                    }
                } elseif ($mapperDriver['type'] === $type) {
                    $matchedAlias[] = $availableAlias;
                }
            }
        }
        return $matchedAlias;
    }

    /**
     * Find alias by driver.
     *
     * @param mixed &$defaultFlag Reference to the default alias.
     * @param mixed &$matchedAlias Reference to the matched alias array.
     * @param string $driver The driver to find aliases for.
     * @param string|null $type Optional. The type of alias.
     *
     * @return array|string  The array of matched aliases or a single matched alias.
     */
    private static function findAliasByDriver(
        mixed &$defaultFlag,
        mixed &$matchedAlias,
        string $driver,
        ?string $type = null
    ): array|string {
        self::$availableAliases = self::getAvailableAliases();
        self::$mapperAliases = self::getMapperAliases();
        foreach (self::$availableAliases as $availableAlias) {
            $matchedAlias = self::matchAliasByDriver(
                self::$mapperAliases,
                $defaultFlag,
                $availableAlias,
                $matchedAlias,
                $driver,
                $type
            );
        }
        return $matchedAlias;
    }

    /**
     * Get alias by driver.
     *
     * @param string $driver The driver to get the alias for.
     * @param string|null $type Optional. The type of alias.
     *
     * @return array|string  The array of matched aliases or a single matched alias.
     */
    public static function getAliasByDriver(string $driver, ?string $type = null): array|string
    {
        $matchedAlias = [];
        $defaultFlag = null;
        $matchedAlias = self::findAliasByDriver($defaultFlag, $matchedAlias, $driver, $type);
        if (empty($matchedAlias) && $defaultFlag !== null) {
            $matchedAlias[] = $defaultFlag;
        }

        return reset($matchedAlias);
    }
}

