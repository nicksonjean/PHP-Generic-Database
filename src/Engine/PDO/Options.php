<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Engine\PDOEngine;

class Options
{
    private static $options = [];

    /**
     * This method is responsible for obtain all options already defined by user
     *
     * @param ?string|null $type
     * @return array
     */
    public static function getOptions(?string $type = null): array
    {
        return !is_null($type) ? self::$options[$type] : self::$options;
    }

    /**
     * This method is responsible for set options before connect in database
     *
     * @param ?array|null $type
     * @return void
     */
    public static function setOptions(?array $options = null): void
    {
        if (!in_array(PDOEngine::getInstance()->getDriver(), (array) \PDO::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                [PDOEngine::getInstance()->getDriver(), implode(', ', (array) \PDO::getAvailableDrivers())]
            );
            throw new \Exception($message);
        }

        $options += [\PDO::ATTR_ERRMODE => (PDOEngine::getInstance()->getException()) ? \PDO::ERRMODE_WARNING : \PDO::ERRMODE_SILENT];
        switch (PDOEngine::getInstance()->getDriver()) {
            case 'mysql':
                if (PDOEngine::getInstance()->getCharset()) {
                    $options += [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . PDOEngine::getInstance()->getCharset() . "';"];
                }
                $options += [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true];
                // Fall-through intencional
            case 'pgsql':
                $options += [\PDO::ATTR_AUTOCOMMIT => true];
                break;
            case 'sqlsrv':
                $options += [\PDO::SQLSRV_ATTR_ENCODING => \PDO::SQLSRV_ENCODING_SYSTEM];
                break;
            case 'sqlite':
                unset(PDOEngine::getInstance()->user, PDOEngine::getInstance()->password);
                break;
            default:
                $options += [\PDO::ATTR_ORACLE_NULLS => \PDO::NULL_EMPTY_STRING];
        }
        self::$options = $options;
    }

    /**
     * This method is responsible for set options after connect in database
     *
     * @return void
     */
    public static function define(): void
    {
        $instance = PDOEngine::getInstance();
        switch ($instance->getDriver()) {
            case 'mysql':
                if ($instance->getCharset()) {
                    $instance->getConnection()->exec("SET NAMES '{$instance->getCharset()}'");
                }
                break;
            case 'pgsql':
                if ($instance->getCharset()) {
                    $instance->getConnection()->exec("SET CLIENT_ENCODING TO '{$instance->getCharset()}'");
                }
                break;
            case 'sqlite':
                $instance->getConnection()->query('PRAGMA foreign_keys = ON');
                break;
        }
    }
}
