<?php

namespace GenericDatabase\Engine\SQLSrv;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLSrvEngine;
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
        $serverInfo = sqlsrv_server_info(SQLSrvEngine::getInstance()->getConnection());
        $clientVersion = sqlsrv_client_info(SQLSrvEngine::getInstance()->getConnection());
        return [
            'server_info' => $serverInfo,
            'client_version' => $clientVersion,
            'server_version' => $serverInfo['SQLServerVersion']
        ];
    }

    private static function connectionStatus(): string
    {
        return (Compare::connection(SQLSrvEngine::getInstance()->getConnection()) === 'sqlsrv')
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', SQLSrvEngine::getInstance()->getHost())
            : 'Connection failed;';
    }

    /**
     * Define all SQLSrv attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool) Options::getOptions(SQLSrv::ATTR_AUTOCOMMIT) ?: false,
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool) Options::getOptions(SQLSrv::ATTR_PERSISTENT) ?: false,
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(SQLSrv::ATTR_DEFAULT_FETCH_MODE) ?? SQLSrv::FETCH_BOTH,
                'CHARACTER_SET' => SQLSrvEngine::getInstance()->getCharset(),
                'COLLATION' => SQLSrvEngine::getInstance()->getCharset() == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new CustomException("Invalid attribute: $attribute"),
            };
        }
        SQLSrvEngine::getInstance()->setAttributes($result);
    }
}
