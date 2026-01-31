<?php

namespace GenericDatabase\Engine\CSV\Connection\Attributes;

use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Compare;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\CSV\Connection\CSV;

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
     * Define all CSV attribute of the connection a ready exist
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
                'AUTOCOMMIT' => (bool)$this->getOptionsHandler()->getOptions(CSV::ATTR_AUTOCOMMIT),
                'CASE' => 0,
                'ERRMODE' => 1,
                'CONNECTION_STATUS' => $this->connectionStatus(),
                'PERSISTENT' => (bool)$this->getOptionsHandler()->getOptions(CSV::ATTR_PERSISTENT),
                'TIMEOUT' =>  (int) $this->getOptionsHandler()->getOptions(CSV::ATTR_CONNECT_TIMEOUT) ?: 30,
                'EMULATE_PREPARES' => true,
                'DEFAULT_FETCH_MODE' => $this->getOptionsHandler()->getOptions(CSV::ATTR_DEFAULT_FETCH_MODE) ?? CSV::FETCH_BOTH,
                default => throw new Exceptions("Invalid attribute: $attribute"),
            };
        }
        $this->set('attributes', $result);
    }
}
