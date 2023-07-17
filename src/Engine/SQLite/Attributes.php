<?php

namespace GenericDatabase\Engine\SQLite;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Engine\SQLite\Options;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\GenericException;
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

    /**
     * Define all SQLite attibute of the conection a ready exist
     *
     * @return void
     * @throws GenericException
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        $memory = SQLiteEngine::getInstance()->getDatabase() === 'memory' ? '' : ' via File';
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT', 'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['versionString'],
                'CONNECTION_STATUS' => (Compare::connection(
                    SQLiteEngine::getInstance()->getConnection()
                ) === 'sqlite')
                    ? sprintf(
                        'Connection OK in %s%s; waiting to send.',
                        SQLiteEngine::getInstance()->getDatabase(),
                        $memory
                    )
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
                'DEFAULT_FETCH_MODE' => 3,
                default => throw new GenericException("Invalid attribute: $attribute"),
            };
        }
        SQLiteEngine::getInstance()->setAttributes($result);
    }
}
