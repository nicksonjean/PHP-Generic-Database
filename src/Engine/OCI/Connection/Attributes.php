<?php

namespace GenericDatabase\Engine\OCI\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\OCIConnection;
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
        $serverInfo = oci_server_version(OCIConnection::getInstance()->getConnection());
        $version = preg_replace('~^.* (\d+\.\d+\.\d+\.\d+\.\d+).*~s', '\1', $serverInfo);
        return [
            'server_info' => $serverInfo,
            'client_version' => oci_client_version(),
            'server_version' => $version
        ];
    }

    /** @noinspection PhpUnused */
    private static function connectionStatus(): string
    {
        return (Compare::connection(OCIConnection::getInstance()->getConnection()) === 'oci')
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', OCIConnection::getInstance()->getHost())
            : 'Connection failed;';
    }

    /**
     * Define all OCI attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)Options::getOptions(OCI::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => self::connectionStatus(),
                'PERSISTENT' => (bool)Options::getOptions(OCI::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) Options::getOptions(OCI::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(OCI::ATTR_DEFAULT_FETCH_MODE) ?? OCI::FETCH_BOTH,
                'CHARACTER_SET' => OCIConnection::getInstance()->getCharset(),
                'COLLATION' => OCIConnection::getInstance()->getCharset() == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        OCIConnection::getInstance()->setAttributes($result);
    }
}
