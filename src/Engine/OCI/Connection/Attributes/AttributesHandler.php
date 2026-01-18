<?php

namespace GenericDatabase\Engine\OCI\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\OCI\Connection\OCI;

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
        $serverInfo = oci_server_version($this->getInstance()->getConnection());
        $version = preg_replace('~^.* (\d+\.\d+\.\d+\.\d+\.\d+).*~s', '\1', $serverInfo);
        return [
            'server_info' => $serverInfo,
            'client_version' => oci_client_version(),
            'server_version' => $version
        ];
    }

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        return (Compare::connection($this->getInstance()->getConnection()) === 'oci')
            ? sprintf(
                'Connection OK in %s via TCP/IP; waiting to send.',
                $this->get('host')
            )
            : 'Connection failed;';
    }

    /**
     * Define all OCI attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)$this->getOptionsHandler()->getOptions(OCI::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(OCI::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) $this->getOptionsHandler()->getOptions(OCI::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(OCI::ATTR_DEFAULT_FETCH_MODE) ?? OCI::FETCH_BOTH,
                'CHARACTER_SET' => $this->get('charset'),
                'COLLATION' => $this->get('charset') == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
