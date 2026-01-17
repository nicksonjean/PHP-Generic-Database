<?php

namespace GenericDatabase\Engine\JSON\Connection\Options;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\JSON\Connection\JSON;
use GenericDatabase\Abstract\AbstractOptions;

class OptionsHandler extends AbstractOptions implements IOptions
{
    /**
     * This method is responsible for set options before connect in database
     *
     * @param ?array $options = null
     * @return void
     * @throws ReflectionException
     */
    public function setOptions(?array $options = null): void
    {
        if ($options === null) {
            return;
        }
        $class = JSON::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "JSON", $key)
                    : $key;
                $this->getInstance()->setAttribute("JSON::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    $this->getInstance()->setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] = constant("$class::" . str_replace("JSON::", '', $options[$value]));
                } else {
                    self::$options[constant("$class::$key")] = $options[$value];
                }
            }
        }
    }

    /**
     * This method is responsible for set options after connect in database
     *
     * @return void
     */
    public function define(): void
    {
        foreach (array_keys($this->getOptions()) as $key) {
            if ($key === 'ATTR_PERSISTENT' && ini_get('json.allow_persistent') !== '1') {
                ini_set('json.allow_persistent', 1);
            }
        }
    }
}
