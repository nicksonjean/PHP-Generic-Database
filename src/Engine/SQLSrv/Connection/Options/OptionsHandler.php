<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\Options;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
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
        $class = SQLSrv::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "SQLSRV", $key)
                    : $key;
                $this->getInstance()->setAttribute("SQLSrv::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    $this->getInstance()->setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] = constant("$class::" . str_replace("SQLSrv::", '', $options[$value]));
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
            if ($key === 'ATTR_CONNECT_TIMEOUT' && ini_get('sqlsrv.persistent_timeout') !== '1') {
                ini_set('sqlsrv.persistent_timeout', $this->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT));
            }
        }
    }
}

