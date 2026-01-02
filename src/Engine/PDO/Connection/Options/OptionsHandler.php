<?php

namespace GenericDatabase\Engine\PDO\Connection\Options;

use ReflectionException;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IOptions;
use PDO;
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
        if (!in_array($this->get('driver'), PDO::getAvailableDrivers())) {
            throw new Exceptions(sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                $this->get('driver'),
                implode(', ', PDO::getAvailableDrivers())
            ));
        }

        $options += [PDO::ATTR_ERRMODE => ($this->get('exception'))
            ? PDO::ERRMODE_WARNING
            : PDO::ERRMODE_SILENT];
        switch ($this->get('driver')) {
            case 'mysql':
                if ($this->get('charset')) {
                    $initCommandAttr = (PHP_VERSION_ID >= 80500 && class_exists('Pdo\Mysql'))
                        ? \Pdo\Mysql::ATTR_INIT_COMMAND
                        : PDO::MYSQL_ATTR_INIT_COMMAND;
                    $options += [$initCommandAttr => sprintf(
                        "SET NAMES '%s';",
                        $this->get('charset')
                    )];
                }
                $bufferedQueryAttr = (PHP_VERSION_ID >= 80500 && class_exists('Pdo\Mysql'))
                    ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY
                    : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
                $options += [$bufferedQueryAttr => true];
                break;
            case 'pgsql':
                $options += [PDO::ATTR_AUTOCOMMIT => true];
                break;
            case 'sqlsrv':
                if ($this->get('charset')) {
                    $options += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8];
                } else {
                    $options += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_SYSTEM];
                }
                break;
            case 'sqlite':
                unset($this->getInstance()->user, $this->getInstance()->password);
                break;
            default:
                $options += [PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING];
        }
        self::$options = $options;
    }

    /**
     * This method is responsible for set options after connect in database
     *
     * @return void
     */
    public function define(): void
    {
        if ($this->get('driver') == 'mysql' && $this->get('charset')) {
            $this->getInstance()->getConnection()->exec(sprintf("SET NAMES '%s'", $this->get('charset')));
        } elseif ($this->get('driver') == 'pgsql' && $this->get('charset')) {
            $this->getInstance()->getConnection()->exec(sprintf("SET CLIENT_ENCODING TO '%s'", $this->get('charset')));
        } elseif ($this->get('driver') == 'sqlite') {
            $this->getInstance()->getConnection()->query('PRAGMA foreign_keys = ON');
        }
    }
}
