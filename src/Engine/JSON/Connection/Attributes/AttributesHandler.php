<?php

namespace GenericDatabase\Engine\JSON\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\JSON\Connection\JSON;

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
        'CONNECTION_STATUS',
        'PERSISTENT',
        'TIMEOUT',
        'EMULATE_PREPARES',
        'DEFAULT_FETCH_MODE'
    ];

    /** @noinspection PhpUnused */
    private function connectionStatus(): string
    {
        return (!empty($this->get('database')) && is_dir($this->get('database')))
            ? vsprintf(
                'Connection OK in %s; waiting to send.',
                [
                    $this->get('database')
                ]
            )
            : 'Connection failed;';
    }

    /**
     * Define all JSON attribute of the connection a ready exist
     *
     * @return void
     * @throws Exceptions
     */
    public function define(): void
    {
        $result = [];
        $keys = array_keys(self::$attributeList);
        foreach ($keys as $key) {
            $attribute = self::$attributeList[$key];
            $result[$attribute] = match ($attribute) {
                'AUTOCOMMIT' => (bool)$this->getOptionsHandler()->getOptions(JSON::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(JSON::ATTR_PERSISTENT),
                'TIMEOUT' =>  (int) $this->getOptionsHandler()->getOptions(JSON::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(JSON::ATTR_DEFAULT_FETCH_MODE) ?? JSON::FETCH_BOTH,
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}

