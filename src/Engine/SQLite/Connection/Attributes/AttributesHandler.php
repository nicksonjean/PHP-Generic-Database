<?php

namespace GenericDatabase\Engine\SQLite\Connection\Attributes;

use AllowDynamicProperties;
use GenericDatabase\Generic\Connection\Instance;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Engine\SQLite\Connection\Options;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use SQLite3;

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
        'DEFAULT_FETCH_MODE'
    ];

    private function settings(): array
    {
        $version = SQLite3::version();
        return [
            'versionString' => $version['versionString'],
            'versionNumber' => $version['versionNumber']
        ];
    }

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        $dbType = $this->get('database') == 'memory' ? '' : ' via File';
        return (Compare::connection($this->getInstance()->getConnection()) === 'sqlite')
            ? vsprintf(
                'Connection OK in %s%s; waiting to send.',
                [
                    $this->get('database'),
                    $dbType
                ]
            )
            : 'Connection failed;';
    }

    /**
     * Define all SQLite attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)Options::getOptions(SQLite::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CLIENT_VERSION' => $settings['versionString'],
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)Options::getOptions(SQLite::ATTR_PERSISTENT),
                'SERVER_INFO' => $settings,
                'SERVER_VERSION' => $settings['versionNumber'],
                'TIMEOUT' =>  (int) Options::getOptions(SQLite::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => Options::getOptions(SQLite::ATTR_DEFAULT_FETCH_MODE) ?? SQLite::FETCH_BOTH,
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
