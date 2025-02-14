<?php

namespace GenericDatabase\Engine\ODBC\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;

#[AllowDynamicProperties]
class Attributes
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

    private static function settings(): array
    {
        return [
            ...ODBC::getDriverSettingsByDriver(ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver())),
            'Alias' => ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver())
        ];
    }

    /** @noinspection PhpUnused */
    private static function connectionStatus(): string
    {
        $dbFiles = ['sqlite', 'access', 'excel', 'text'];
        $dbType = (in_array(ODBCConnection::getInstance()->getDriver(), $dbFiles))
            ? ODBCConnection::getInstance()->getDatabase() . ' via File'
            : ODBCConnection::getInstance()->getHost() . ' via TCP/IP';
        return (Compare::connection(ODBCConnection::getInstance()->getConnection()) === 'odbc')
            ? sprintf('Connection OK in %s; waiting to send.', $dbType)
            : 'Connection failed;';
    }

    /**
     * Define all ODBC attribute of the connection a ready exist
     *
     * @return void
     * @throws Exceptions
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool)Options::getOptions(ODBC::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION', 'SERVER_VERSION' => $settings['DriverODBCVer'] ?? '',
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool)Options::getOptions(ODBC::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings,
                'TIMEOUT' => (int) Options::getOptions(ODBC::ATTR_CONNECT_TIMEOUT) ?: $settings['CPTimeout'] ?? 0,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) ?? ODBC::FETCH_BOTH,
                'CHARACTER_SET', 'COLLATION' => ODBCConnection::getInstance()->getCharset() ?? '',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        ODBCConnection::getInstance()->setAttributes($result);
    }
}
