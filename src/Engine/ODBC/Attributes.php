<?php

namespace GenericDatabase\Engine\ODBC;

use AllowDynamicProperties;
use GenericDatabase\Engine\ODBCEngine;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\CustomException;

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
            ...ODBC::getDriverSettingsByDriver(ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null)),
            'Alias' => ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null)
        ];
    }

    private static function status(): string
    {
        $dbType = '';
        $dbFiles = ['sqlite', 'access', 'excel', 'text'];
        if (in_array(ODBCEngine::getInstance()->getDriver(), $dbFiles)) {
            $dbType = ODBCEngine::getInstance()->getDatabase() . ' via File';
        } else {
            $dbType = ODBCEngine::getInstance()->getHost() . ' via TCP/IP';
        }
        return (Compare::connection(ODBCEngine::getInstance()->getConnection()) === 'odbc')
            ? sprintf(
                'Connection OK in %s; waiting to send.',
                $dbType
            )
            : 'Connection failed;';
    }

    /**
     * Define all ODBC attribute of the connection a ready exist
     *
     * @return void
     * @throws CustomException
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => false,
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['DriverODBCVer'] ?? '',
                'CONNECTION_STATUS' => self::status(),
                'PERSISTENT' => (int) !Options::getOptions(ODBC::ATTR_PERSISTENT)
                    ? 0
                    : (int) Options::getOptions(ODBC::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings,
                'SERVER_VERSION' => $settings['DriverODBCVer'] ?? '',
                'TIMEOUT' => (int) Options::getOptions(ODBC::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(ODBC::ATTR_CONNECT_TIMEOUT)
                    : $settings['CPTimeout'] ?? '',
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => 3,
                'CHARACTER_SET' => ODBCEngine::getInstance()->getCharset() ?? '',
                'COLLATION' => ODBCEngine::getInstance()->getCharset() ?? '',
                default => throw new CustomException("Invalid attribute: $attribute"),
            };
        }
        ODBCEngine::getInstance()->setAttributes($result);
    }
}
