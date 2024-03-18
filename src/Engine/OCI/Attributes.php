<?php

namespace GenericDatabase\Engine\OCI;

use AllowDynamicProperties;
use GenericDatabase\Engine\OCIEngine;
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
        $serverInfo = oci_server_version(OCIEngine::getInstance()->getConnection());
        $version = preg_replace('~^.* (\d+\.\d+\.\d+\.\d+\.\d+).*~s', '\1', $serverInfo);
        return [
            'server_info' => $serverInfo,
            'client_version' => oci_client_version(),
            'server_version' => $version
        ];
    }

    /**
     * Define all OCI attribute of the connection a ready exist
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
                'AUTOCOMMIT' => false,
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => (Compare::connection(
                    OCIEngine::getInstance()->getConnection()
                ) === 'oci')
                    ? sprintf(
                        'Connection OK in %s via TCP/IP; waiting to send.',
                        OCIEngine::getInstance()->getHost()
                    )
                    : 'Connection failed;',
                'PERSISTENT' => (int) !Options::getOptions(OCI::ATTR_PERSISTENT)
                    ? 0
                    : (int) Options::getOptions(OCI::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) Options::getOptions(OCI::ATTR_CONNECT_TIMEOUT)
                    ? Options::getOptions(OCI::ATTR_CONNECT_TIMEOUT)
                    : 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => 3,
                'CHARACTER_SET' => OCIEngine::getInstance()->getCharset(),
                'COLLATION' => OCIEngine::getInstance()->getCharset() == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new CustomException("Invalid attribute: $attribute"),
            };
        }
        OCIEngine::getInstance()->setAttributes($result);
    }
}
