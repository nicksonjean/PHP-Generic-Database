<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Engine\PgSQL\Options;

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

    public static function getFlags()
    {
        $flags = 0;
        if (Options::getOptions(PgSQL::ATTR_CONNECT_FORCE_NEW)) {
            $flags |= PGSQL_CONNECT_FORCE_NEW;
        } elseif (Options::getOptions(PgSQL::ATTR_CONNECT_ASYNC)) {
            $flags |= PGSQL_CONNECT_ASYNC;
        }
        return $flags;
    }

    /**
     * Define all PgSQL attibute of the conection a ready exist
     *
     * @return void
     */
    public static function define(): void
    {
        $version = pg_version(PgSQLEngine::getInstance()->getConnection());
        $collate = pg_fetch_object(pg_query(PgSQLEngine::getInstance()->getConnection(), "SHOW LC_COLLATE"));
        $result = [];
        $keys = array_keys(self::$attributeList);

        foreach ($keys as $key) {
            $result[self::$attributeList[$key]] = match (self::$attributeList[$key]) {
                'AUTOCOMMIT' => (int) 0,
                'ERRMODE' => (int) 1,
                'CASE' => (int) 0,
                'CLIENT_VERSION' => $version['client'],
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
                    $version['is_superuser'],
                    $version['session_authorization'],
                    $version['DateStyle']
                ),
                'SERVER_VERSION' => $version['server'],
                'TIMEOUT' => (int) Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                    : 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => (int) 3,
                'CHARACTER_SET' => pg_client_encoding(PgSQLEngine::getInstance()->getConnection()),
                'COLLATION' => ($collate !== false && property_exists($collate, 'lc_collate'))
                    ? $collate->lc_collate
                    : false
            };
        };

        PgSQLEngine::getInstance()->setAttributes((array) $result);
    }
}
