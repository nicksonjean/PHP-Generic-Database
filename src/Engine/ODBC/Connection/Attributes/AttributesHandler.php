<?php

namespace GenericDatabase\Engine\ODBC\Connection\Attributes;

use AllowDynamicProperties;
use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\ODBC\Connection\ODBC;

#[AllowDynamicProperties]
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
                'CLIENT_VERSION' => $settings['DriverODBCVer'] ?? '',
                'SERVER_VERSION' => $settings['DriverODBCVer'] ?? '',
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(ODBC::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings,
                'TIMEOUT' => (int) $this->getOptionsHandler()->getOptions(ODBC::ATTR_CONNECT_TIMEOUT) ?: $settings['CPTimeout'] ?? 0,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(ODBC::ATTR_DEFAULT_FETCH_MODE) ?? ODBC::FETCH_BOTH,
                'CHARACTER_SET' => $this->get('charset') ?? '',
                'COLLATION' => $this->get('charset') ?? '',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
