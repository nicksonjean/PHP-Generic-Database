<?php

namespace GenericDatabase\Engine\SQLSrv\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLSrvConnection;
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
        $serverInfo = sqlsrv_server_info(SQLSrvConnection::getInstance()->getConnection());
        $clientVersion = sqlsrv_client_info(SQLSrvConnection::getInstance()->getConnection());
        return [
            'server_info' => $serverInfo,
            'client_version' => $clientVersion,
            'server_version' => $serverInfo['SQLServerVersion']
        ];
    }

    /** @noinspection PhpUnused */
    private static function connectionStatus(): string
    {
        return (Compare::connection(SQLSrvConnection::getInstance()->getConnection()) === 'sqlsrv')
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', SQLSrvConnection::getInstance()->getHost())
            : 'Connection failed;';
    }

    /**
     * Define all SQLSrv attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)Options::getOptions(SQLSrv::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool)Options::getOptions(SQLSrv::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(SQLSrv::ATTR_DEFAULT_FETCH_MODE) ?? SQLSrv::FETCH_BOTH,
                'CHARACTER_SET' => SQLSrvConnection::getInstance()->getCharset(),
                'COLLATION' => SQLSrvConnection::getInstance()->getCharset() == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        SQLSrvConnection::getInstance()->setAttributes($result);
    }
}
