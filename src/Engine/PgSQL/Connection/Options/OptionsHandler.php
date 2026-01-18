<?php

namespace GenericDatabase\Engine\PgSQL\Connection\Options;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
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
        $class = PgSQL::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "PGSQL", $key)
                    : $key;
                $this->getInstance()->setAttribute("PgSQL::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    $this->getInstance()->setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] = constant("$class::" . str_replace("PgSQL::", '', $options[$value]));
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
            switch ($key) {
                case PgSQL::ATTR_PERSISTENT:
                    if (ini_get('pgsql.allow_persistent') !== '1') {
                        ini_set('pgsql.allow_persistent', 1);
                    }
                    break;
                default:
                    if ($this->get('charset')) {
                        pg_set_client_encoding($this->getInstance()->getConnection(), $this->get('charset'));
                    }
            }
        }
    }
}

