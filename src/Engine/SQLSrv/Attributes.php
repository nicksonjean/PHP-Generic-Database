<?php

namespace GenericDatabase\Engine\SQLSrv;

use GenericDatabase\Engine\SQLSrvEngine;
use GenericDatabase\Engine\SQLSrv\Options;

class Attributes
{
    /**
     * static attributes constants
     *
     */
    public static $attributeList = [
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

    private static function settings()
    {
        $serverInfo = sqlsrv_server_info(SQLSrvEngine::getInstance()->getConnection());
        $clientVersion = sqlsrv_client_info(SQLSrvEngine::getInstance()->getConnection());
        return [
            'server_info' => $serverInfo,
            'client_version' => $clientVersion,
            'server_version' => $serverInfo['SQLServerVersion']
        ];
    }

    /**
     * Define all SQLSrv attibute of the conection a ready exist
     *
     * @return void
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);

        foreach ($keys as $key) {
            $result[self::$attributeList[$key]] = match (self::$attributeList[$key]) {
                'AUTOCOMMIT' => (int) 0,
                'ERRMODE' => (int) 1,
                'CASE' => (int) 0,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => SQLSrvEngine::getInstance()->getConnection()
                    ? 'Connection OK; waiting to send.'
                    : 'Connection failed;',
                'PERSISTENT' => (int) !Options::getOptions(SQLSrv::ATTR_PERSISTENT)
                    ? 0
                    : (int) Options::getOptions(SQLSrv::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                    : 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => (int) 3,
                'CHARACTER_SET' => SQLSrvEngine::getInstance()->getCharset(),
                'COLLATION' => SQLSrvEngine::getInstance()->getCharset() === 'utf8' ? 'unicode_ci_ai' : 'none',
            };
        };
        SQLSrvEngine::getInstance()->setAttributes((array) $result);
    }
}
