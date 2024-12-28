<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\PgSQLConnection;
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
        $version = pg_version(PgSQLConnection::getInstance()->getConnection());
        $collateQuery = sprintf(
            "SELECT datcollate as lc_collate from pg_database WHERE datname = '%s'",
            PgSQLConnection::getInstance()->getDatabase()
        );
        $collate = pg_fetch_object(pg_query(PgSQLConnection::getInstance()->getConnection(), $collateQuery));
        return [
            'collate' => $collate,
            'version' => $version
        ];
    }

    /** @noinspection PhpUnused */
    private static function serverInfo(): string
    {
        $settings = self::settings();
        return sprintf(
            "PID: %s; Client Encoding: %s; Is Superuser: %s; Session Authorization: %s; Date Style: %s",
            pg_get_pid(PgSQLConnection::getInstance()->getConnection()),
            pg_client_encoding(PgSQLConnection::getInstance()->getConnection()),
            $settings['version']['is_superuser'],
            $settings['version']['session_authorization'],
            $settings['version']['DateStyle']
        );
    }

    /** @noinspection PhpUnused */
    private static function connectionStatus(): string
    {
        return (
            Compare::connection(PgSQLConnection::getInstance()->getConnection()) === 'pgsql'
            && pg_connection_status(PgSQLConnection::getInstance()->getConnection()) === PGSQL_CONNECTION_OK
        )
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', PgSQLConnection::getInstance()->getHost())
            : 'Connection failed;';
    }

    /**
     * Define all PgSQL attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool) Options::getOptions(PgSQL::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['version']['client'],
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool) Options::getOptions(PgSQL::ATTR_PERSISTENT),
                'SERVER_INFO' => self::serverInfo(),
                'SERVER_VERSION' => $settings['version']['server'],
                'TIMEOUT' => (int) Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(PgSQL::ATTR_DEFAULT_FETCH_MODE) ?? PgSQL::FETCH_BOTH,
                'CHARACTER_SET' => pg_client_encoding(PgSQLConnection::getInstance()->getConnection()),
                'COLLATION' => ($settings['collate'] !== false && property_exists($settings['collate'], 'lc_collate'))
                ? $settings['collate']->lc_collate
                : false,
                default => throw new CustomException("Invalid attribute: $attribute"),
            };
        }
        PgSQLConnection::getInstance()->setAttributes($result);
    }
}
