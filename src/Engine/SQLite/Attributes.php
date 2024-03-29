<?php

namespace GenericDatabase\Engine\SQLite;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\CustomException;
use SQLite3;

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
        'DEFAULT_FETCH_MODE'
    ];

    private static function settings(): array
    {
        $version = SQLite3::version();
        return [
            'versionString' => $version['versionString'],
            'versionNumber' => $version['versionNumber']
        ];
    }

    private static function connectionStatus(): string
    {
        $dbType = SQLiteEngine::getInstance()->getDatabase() == 'memory' ? '' : ' via File';
        return (Compare::connection(SQLiteEngine::getInstance()->getConnection()) === 'sqlite')
            ? sprintf('Connection OK in %s%s; waiting to send.', SQLiteEngine::getInstance()->getDatabase(), $dbType)
            : 'Connection failed;';
    }

    /**
     * Define all SQLite attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool) Options::getOptions(SQLite::ATTR_AUTOCOMMIT) ?: false,
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['versionString'],
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool) Options::getOptions(SQLite::ATTR_PERSISTENT) ?: false,
                'SERVER_INFO' => $settings,
                'SERVER_VERSION' => $settings['versionNumber'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLite::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(SQLite::ATTR_DEFAULT_FETCH_MODE) ?? SQLite::FETCH_BOTH,
                default => throw new CustomException("Invalid attribute: $attribute"),
            };
        }
        SQLiteEngine::getInstance()->setAttributes($result);
    }
}
