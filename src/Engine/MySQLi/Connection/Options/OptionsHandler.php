<?php

namespace GenericDatabase\Engine\MySQLi\Connection\Options;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;
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
        $class = MySQL::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "MYSQLI", $key)
                    : $key;
                $this->getInstance()->setAttribute("MySQL::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    MySQL::setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] = constant("$class::" . str_replace("MySQL::", '', $options[$value]));
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
        foreach ($this->getOptions() as $key => $value) {
            switch ($key) {
                case MySQL::ATTR_PERSISTENT:
                    if (ini_get('mysqli.allow_persistent') !== '1') {
                        ini_set('mysqli.allow_persistent', 1);
                    }
                    break;
                case MySQL::ATTR_OPT_LOCAL_INFILE:
                    if (ini_get('mysqli.allow_local_infile') !== '1') {
                        ini_set('mysqli.allow_local_infile', 1);
                    }
                    break;
                case MySQL::ATTR_INIT_COMMAND:
                    $this->getInstance()->getConnection()->query($value);
                    break;
                case MySQL::ATTR_SET_CHARSET_NAME:
                    $this->getInstance()->getConnection()->set_charset($value);
                    break;
                case MySQL::ATTR_OPT_CONNECT_TIMEOUT:
                    $this->getInstance()->getConnection()->query("SET GLOBAL connect_timeout=" . $value);
                    $this->getInstance()->getConnection()->query("SET SESSION interactive_timeout=" . $value);
                    $this->getInstance()->getConnection()->query("SET SESSION wait_timeout=" . $value);
                    break;
                case MySQL::ATTR_OPT_READ_TIMEOUT:
                    $this->getInstance()->getConnection()->query("SET SESSION net_read_timeout=" . $value);
                    $this->getInstance()->getConnection()->query("SET SESSION net_write_timeout=" . ($value * 2));
                    break;
                case MySQL::ATTR_REPORT:
                    mysqli_report($this->getOptions($value));
                    break;
                default:
                    $this->getInstance()->getConnection()->query("SET SESSION sql_mode=''");
                    if ($this->get('charset')) {
                        $this->getInstance()->getConnection()->set_charset(
                            $this->get('charset')
                        );
                    }
            }
        }
    }
}
