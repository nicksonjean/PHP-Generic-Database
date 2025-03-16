<?php

namespace GenericDatabase\Engine\PgSQL\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;

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
        $version = pg_version($this->getInstance()->getConnection());
        $collateQuery = sprintf(
            "SELECT datcollate as lc_collate from pg_database WHERE datname = '%s'",
            $this->get('database')
        );
        $collate = pg_fetch_object(pg_query($this->getInstance()->getConnection(), $collateQuery));
        return [
            'collate' => $collate,
            'version' => $version
        ];
    }

    /** @noinspection PhpUnused */
    private function serverInfo(): string
    {
        $settings = $this->settings();
        return vsprintf(
            "PID: %s; Client Encoding: %s; Is Superuser: %s; Session Authorization: %s; Date Style: %s",
            [
                pg_get_pid($this->getInstance()->getConnection()),
                pg_client_encoding($this->getInstance()->getConnection()),
                $settings['version']['is_superuser'],
                $settings['version']['session_authorization'],
                $settings['version']['DateStyle']
            ]
        );
    }

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        return (Compare::connection($this->getInstance()->getConnection()) === 'pgsql' && pg_connection_status($this->getInstance()->getConnection()) === PGSQL_CONNECTION_OK)
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', $this->get('host'))
            : 'Connection failed;';
    }

    /**
     * Define all PgSQL attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool) $this->getOptionsHandler()->getOptions(PgSQL::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['version']['client'],
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool) $this->getOptionsHandler()->getOptions(PgSQL::ATTR_PERSISTENT),
                'SERVER_INFO' => $this->serverInfo(),
                'SERVER_VERSION' => $settings['version']['server'],
                'TIMEOUT' => (int) $this->getOptionsHandler()->getOptions(PgSQL::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(PgSQL::ATTR_DEFAULT_FETCH_MODE) ?? PgSQL::FETCH_BOTH,
                'CHARACTER_SET' => pg_client_encoding($this->getInstance()->getConnection()),
                'COLLATION' => ($settings['collate'] !== false && property_exists($settings['collate'], 'lc_collate'))
                    ? $settings['collate']->lc_collate
                    : false,
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
