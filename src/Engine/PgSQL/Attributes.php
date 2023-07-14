<?php

namespace GenericDatabase\Engine\PgSQL;

use AllowDynamicProperties;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Helpers\GenericException;

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

    public static function getFlags(): int
    {
        $flags = 0;
        if (Options::getOptions(PgSQL::ATTR_CONNECT_FORCE_NEW)) {
            $flags |= PGSQL_CONNECT_FORCE_NEW;
        } elseif (Options::getOptions(PgSQL::ATTR_CONNECT_ASYNC)) {
            $flags |= PGSQL_CONNECT_ASYNC;
        }
        return $flags;
    }

    private static function settings(): array
    {
        $version = pg_version(PgSQLEngine::getInstance()->getConnection());
        $collate = pg_fetch_object(pg_query(PgSQLEngine::getInstance()->getConnection(), "SHOW LC_COLLATE"));
        return [
            'collate' => $collate,
            'version' => $version
        ];
    }

    /**
     * Define all PgSQL attibute of the conection a ready exist
     *
     * @return void
     * @throws GenericException
     */
    public static function define(): void
    {
        $settings = self::settings();
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT', 'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['version']['client'],
                'CONNECTION_STATUS' => (pg_connection_status(
                    PgSQLEngine::getInstance()->getConnection()
                ) === PGSQL_CONNECTION_OK)
                    ? 'Connection OK; waiting to send.'
                    : 'Connection failed;',
                'PERSISTENT' => (int) !Options::getOptions(PgSQL::ATTR_PERSISTENT)
                    ? 0
                    : (int) Options::getOptions(PgSQL::ATTR_PERSISTENT),
                'SERVER_INFO' => sprintf(
                    "PID: %s; Client Encoding: %s; Is Superuser: %s; Session Authorization: %s; Date Style: %s",
                    pg_get_pid(PgSQLEngine::getInstance()->getConnection()),
                    pg_client_encoding(PgSQLEngine::getInstance()->getConnection()),
                    $settings['version']['is_superuser'],
                    $settings['version']['session_authorization'],
                    $settings['version']['DateStyle']
                ),
                'SERVER_VERSION' => $settings['version']['server'],
                'TIMEOUT' => (int) Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                    : 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => 3,
                'CHARACTER_SET' => pg_client_encoding(PgSQLEngine::getInstance()->getConnection()),
                'COLLATION' => ($settings['collate'] !== false && property_exists($settings['collate'], 'lc_collate'))
                    ? $settings['collate']->lc_collate
                    : false,
                default => throw new GenericException("Invalid attribute: $attribute"),
            };
        }
        PgSQLEngine::getInstance()->setAttributes($result);
    }
}
