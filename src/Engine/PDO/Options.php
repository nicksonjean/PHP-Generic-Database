<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Engine\PDOEngine;

#[\AllowDynamicProperties]
class Options
{
    private static $options = [];

    /**
     * This method is responsible for obtain all options already defined by user
     *
     * @param ?string $type = null
     * @return array
     */
    public static function getOptions(?string $type = null): array
    {
        return !is_null($type) ? self::$options[$type] : self::$options;
    }

    /**
     * This method is responsible for set options before connect in database
     *
     * @param ?array $options = null
     * @return void
     */
    public static function setOptions(?array $options = null): void
    {
        if (!in_array(PDOEngine::getInstance()->getDriver(), (array) \PDO::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                PDOEngine::getInstance()->getDriver(),
                implode(', ', (array) \PDO::getAvailableDrivers())
            );
            throw new \Exception($message);
        }

        $options += [\PDO::ATTR_ERRMODE => (PDOEngine::getInstance()->getException()) ? \PDO::ERRMODE_WARNING : \PDO::ERRMODE_SILENT];
        switch (PDOEngine::getInstance()->getDriver()) {
            case 'mysql':
                if (PDOEngine::getInstance()->getCharset()) {
                    $options += [\PDO::MYSQL_ATTR_INIT_COMMAND => sprintf("SET NAMES '%s';", PDOEngine::getInstance()->getCharset())];
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
        switch (PDOEngine::getInstance()->getDriver()) {
            case 'mysql':
                if (PDOEngine::getInstance()->getCharset()) {
                    PDOEngine::getInstance()->getConnection()->exec(sprintf("SET NAMES '%s'", PDOEngine::getInstance()->getCharset()));
                }
                break;
            case 'pgsql':
                if (PDOEngine::getInstance()->getCharset()) {
                    PDOEngine::getInstance()->getConnection()->exec(sprintf("SET CLIENT_ENCODING TO '%s'", PDOEngine::getInstance()->getCharset()));
                }
                break;
            case 'sqlite':
                PDOEngine::getInstance()->getConnection()->query('PRAGMA foreign_keys = ON');
                break;
        }
    }
}
