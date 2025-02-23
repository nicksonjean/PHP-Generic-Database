<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\Attributes;

use AllowDynamicProperties;
use GenericDatabase\Generic\Connection\Instance;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Engine\SQLSrv\Connection\Options;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;

#[AllowDynamicProperties]
class AttributesHandler implements IAttributes
{
    use Instance;

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
        $serverInfo = sqlsrv_server_info($this->getInstance()->getConnection());
        $clientVersion = sqlsrv_client_info($this->getInstance()->getConnection());
        return [
            'server_info' => $serverInfo,
            'client_version' => $clientVersion,
            'server_version' => $serverInfo['SQLServerVersion']
        ];
    }

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        return (Compare::connection($this->getInstance()->getConnection()) === 'sqlsrv')
            ? sprintf('Connection OK in %s via TCP/IP; waiting to send.', $this->get('host'))
            : 'Connection failed;';
    }

    /**
     * Define all SQLSrv attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)Options::getOptions(SQLSrv::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['client_version'],
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)Options::getOptions(SQLSrv::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings['server_info'],
                'SERVER_VERSION' => $settings['server_version'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(SQLSrv::ATTR_DEFAULT_FETCH_MODE) ?? SQLSrv::FETCH_BOTH,
                'CHARACTER_SET' => $this->get('charset'),
                'COLLATION' => $this->get('charset') == 'utf8' ? 'unicode_ci_ai' : 'none',
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
