<?php

namespace GenericDatabase\Engine\ODBC\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\ODBC\Connection\ODBC;

class AttributesHandler extends AbstractAttributes implements IAttributes
{
    /**
     * static attributes constants
     *
     */
    public static array $attributeList = [
        'AUTOCOMMIT',
        'ERRMODE',
        'CASE',
        'CLIENT_VERSION',
        'CONNECTION_STATUS',
        'PERSISTENT',
        'SERVER_INFO',
        'SERVER_VERSION',
        'TIMEOUT',
        'EMULATE_PREPARES',
        'DEFAULT_FETCH_MODE',
        'CHARACTER_SET',
        'COLLATION'
    ];

    private function settings(): array
    {
        $driverSettings = ODBC::getDriverSettingsByDriver(ODBC::getAliasByDriver($this->get('driver')));
        return array_merge(
            is_array($driverSettings) ? $driverSettings : [],
            ['Alias' => ODBC::getAliasByDriver($this->get('driver'))]
        );
    }

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        $dbFiles = ['sqlite', 'access', 'excel', 'text'];
        $dbType = (in_array($this->get('driver'), $dbFiles))
            ? $this->get('database') . ' via File'
            : $this->get('host') . ' via TCP/IP';
        return (Compare::connection($this->getInstance()->getConnection()) === 'odbc')
            ? sprintf('Connection OK in %s; waiting to send.', $dbType)
            : 'Connection failed;';
    }

    /**
     * Get database version by executing appropriate query for each driver
     *
     * @return string
     */
    private function databaseVersion(): string
    {
        $driver = $this->get('driver');

        if ($driver === 'pgsql') {
            if (function_exists('shell_exec')) {
                $version = @shell_exec('pg_config --version');
                if ($version !== null && $version !== false) {
                    return trim($version);
                }
            }
            return '';
        }

        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return '';
        }

        $isValidConnection = is_resource($connection) || (PHP_VERSION_ID >= 80400 && is_object($connection) && get_class($connection) === 'Odbc\Connection');

        if (!$isValidConnection) {
            return '';
        }

        if (!function_exists('odbc_exec') || !function_exists('odbc_result')) {
            return '';
        }

        $isPhp80Firebird = (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80100) &&  in_array($driver, ['firebird', 'ibase']);

        if ($isPhp80Firebird) {
            return '';
        }

        $query = match ($driver) {
            'oci' => "SELECT * FROM V\$VERSION",
            'sqlsrv', 'mssql', 'dblib', 'sybase' => "SELECT @@VERSION",
            'sqlite' => "SELECT sqlite_version()",
            'mysql', 'mariadb' => "SELECT VERSION()",
            'firebird', 'ibase' => "SELECT RDB\$GET_CONTEXT('SYSTEM', 'ENGINE_VERSION') FROM RDB\$DATABASE",
            'access', 'excel', 'text' => '',
            default => '',
        };

        if (empty($query)) {
            return '';
        }

        $result = @odbc_exec($connection, $query);
        if ($result === false || !$result) {
            return '';
        }

        $version = '';

        $isValidResult = is_resource($result) || (PHP_VERSION_ID >= 80400 && is_object($result) && get_class($result) === 'Odbc\Result');

        if ($isValidResult && function_exists('odbc_num_fields') && function_exists('odbc_field_name')) {
            $numFields = odbc_num_fields($result);
            if ($numFields > 0) {
                if (function_exists('odbc_fetch_row')) {
                    @odbc_fetch_row($result);
                }

                for ($i = 1; $i <= $numFields; $i++) {
                    $resultValue = false;
                    $fieldName = @odbc_field_name($result, $i);
                    $resultValue = $fieldName ? @odbc_result($result, $fieldName) : false;
                    if ($resultValue === false || $resultValue === null || $resultValue === '') {
                        $resultValue = @odbc_result($result, $i);
                    }

                    if ($resultValue !== false && $resultValue !== null && $resultValue !== '') {
                        $version = trim((string)$resultValue);
                        break;
                    }
                }

                if (!empty($version) && in_array($driver, ['sqlsrv', 'mssql', 'dblib', 'sybase'])) {
                    $version = preg_replace('/[\r\n\t]+/', ' ', $version);
                    $version = preg_replace('/\s+/', ' ', $version);
                    $version = trim($version);
                }
            }
        }

        if ($isValidResult && function_exists('odbc_free_result')) {
            @odbc_free_result($result);
        }

        return $version;
    }

    /**
     * Define all ODBC attribute of the connection a ready exist
     *
     * @return void
     * @throws Exceptions
     */
    public function define(): void
    {
        $settings = $this->settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool)$this->getOptionsHandler()->getOptions(ODBC::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION', 'SERVER_VERSION' => !empty($settings['DriverODBCVer'] ?? '')
                    ? $settings['DriverODBCVer']
                    : $this->databaseVersion(),
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(ODBC::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings,
                'TIMEOUT' => (int) $this->getOptionsHandler()->getOptions(ODBC::ATTR_CONNECT_TIMEOUT) ?: $settings['CPTimeout'] ?? 0,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) ?? ODBC::FETCH_BOTH,
                'CHARACTER_SET', 'COLLATION' => $this->get('charset') ?? '',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
