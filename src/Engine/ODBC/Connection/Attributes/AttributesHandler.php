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
        return [
            ...ODBC::getDriverSettingsByDriver(ODBC::getAliasByDriver($this->get('driver'))),
            'Alias' => ODBC::getAliasByDriver($this->get('driver'))
        ];
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
        
        // For PostgreSQL, use pg_config --version via shell_exec
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

        if (!function_exists('odbc_exec') || !function_exists('odbc_result')) {
            return '';
        }

        $query = match ($driver) {
            'oci' => "SELECT * FROM V\$VERSION",
            'sqlsrv', 'mssql', 'dblib', 'sybase' => "SELECT @@VERSION",
            'sqlite' => "SELECT sqlite_version()",
            'mysql', 'mariadb' => "SELECT VERSION()",
            'firebird', 'ibase' => "SELECT RDB\$GET_CONTEXT('SYSTEM', 'ENGINE_VERSION') FROM RDB\$DATABASE",
            'access', 'excel', 'text' => '', // File-based drivers don't have database version
            default => '',
        };

        if (empty($query)) {
            return '';
        }

        $result = odbc_exec($connection, $query);
        if ($result === false || !$result) {
            return '';
        }

        $version = '';
       
        // Use unified approach for all drivers - iterate through fields like ODBC::fetchArray does
        if (function_exists('odbc_num_fields') && function_exists('odbc_field_name')) {
            $numFields = odbc_num_fields($result);
            if ($numFields > 0) {
                // Try to fetch row first
                if (function_exists('odbc_fetch_row')) {
                    odbc_fetch_row($result);
                }
                
                // Iterate through fields
                for ($i = 1; $i <= $numFields; $i++) {
                    $fieldName = odbc_field_name($result, $i);
                    // Try by field name first
                    $resultValue = $fieldName ? odbc_result($result, $fieldName) : false;
                    // If that didn't work, try by index
                    if ($resultValue === false || $resultValue === null || $resultValue === '') {
                        $resultValue = odbc_result($result, $i);
                    }
                    // If we got a value, use it and break
                    if ($resultValue !== false && $resultValue !== null && $resultValue !== '') {
                        $version = trim((string)$resultValue);
                        break;
                    }
                }
                
                // Clean up line breaks and multiple spaces for SQL Server
                if (!empty($version) && in_array($driver, ['sqlsrv', 'mssql', 'dblib', 'sybase'])) {
                    $version = preg_replace('/[\r\n\t]+/', ' ', $version);
                    $version = preg_replace('/\s+/', ' ', $version);
                    $version = trim($version);
                }
            }
        }
        
        if ($result && function_exists('odbc_free_result')) {
            odbc_free_result($result);
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
