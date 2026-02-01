<?php

namespace GenericDatabase\Engine\XML\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\XML\Connection\XML;

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
     * Define all XML attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)$this->getOptionsHandler()->getOptions(XML::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(XML::ATTR_PERSISTENT),
                'TIMEOUT' =>  (int) $this->getOptionsHandler()->getOptions(XML::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(XML::ATTR_DEFAULT_FETCH_MODE) ?? XML::FETCH_BOTH,
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
