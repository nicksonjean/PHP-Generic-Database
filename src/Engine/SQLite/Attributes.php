<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLite\Options;

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
        'DEFAULT_FETCH_MODE'
    ];

    private static function settings()
    {
        $version = \SQLite3::version();
        return [
            'versionString' => $version['versionString'],
            'versionNumber' => $version['versionNumber']
        ];
    }

    /**
     * Define all SQLite attibute of the conection a ready exist
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
                'CLIENT_VERSION' => $settings['versionString'],
                'CONNECTION_STATUS' => SQLiteEngine::getInstance()->getConnection()
                    ? 'Connection OK; waiting to send.'
                    : 'Connection failed;',
                'PERSISTENT' => (int) !Options::getOptions(SQLite::ATTR_PERSISTENT)
                    ? 0
                    : (int) Options::getOptions(SQLite::ATTR_PERSISTENT),
                'SERVER_INFO' => '',
                'SERVER_VERSION' => $settings['versionNumber'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLite::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(SQLite::ATTR_CONNECT_TIMEOUT)
                    : 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => (int) 3
            };
        };
        SQLiteEngine::getInstance()?->setAttributes((array) $result);
    }
}
