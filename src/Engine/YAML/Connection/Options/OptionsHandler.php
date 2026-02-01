<?php

namespace GenericDatabase\Engine\YAML\Connection\Options;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\YAML\Connection\YAML;
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
        $class = YAML::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "YAML", $key)
                    : $key;
                $this->getInstance()->setAttribute("YAML::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    $this->getInstance()->setAttribute($keyName, $options[$value]);
                    $this->getInstance()->setAttribute($value, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] = constant("$class::" . str_replace("YAML::", '', $options[$value]));
                } else {
                    self::$options[constant("$class::$key")] = $options[$value];
                }
            }
        }
    }

    /**
     * This method is responsible for set options after connect in database
     * Note: YAML engine has no native PHP extension; persistence is file-based.
     *
     * @return void
     */
    public function define(): void
    {
        // No native yaml extension options to set for file-based YAML engine
    }
}
