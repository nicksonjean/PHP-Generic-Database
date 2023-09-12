<?php

namespace GenericDatabase\Engine\PDO;

use PDO;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Engine\PDOEngine;

class Options
{
    private static array $options = [];

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
     * @throws CustomException
     */
    public static function setOptions(?array $options = null): void
    {
        if (!in_array(PDOEngine::getInstance()->getDriver(), PDO::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                PDOEngine::getInstance()->getDriver(),
                implode(', ', PDO::getAvailableDrivers())
            );
            throw new CustomException($message);
        }

        $options += [PDO::ATTR_ERRMODE => (PDOEngine::getInstance()->getException())
            ? PDO::ERRMODE_WARNING
            : PDO::ERRMODE_SILENT];
        switch (PDOEngine::getInstance()->getDriver()) {
            case 'mysql':
                if (PDOEngine::getInstance()->getCharset()) {
                    $options += [PDO::MYSQL_ATTR_INIT_COMMAND => sprintf(
                        "SET NAMES '%s';",
                        PDOEngine::getInstance()->getCharset()
                    )];
                }
                $options += [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true];
                break;
                // Fall-through intencional
            case 'pgsql':
                $options += [PDO::ATTR_AUTOCOMMIT => true];
                break;
            case 'sqlsrv':
                if (PDOEngine::getInstance()->getCharset()) {
                    $options += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8];
                } else {
                    $options += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_SYSTEM];
                }
                break;
            case 'sqlite':
                unset(PDOEngine::getInstance()->user, PDOEngine::getInstance()->password);
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
    public static function define(): void
    {
        $driver = PDOEngine::getInstance()->getDriver();
        $charset = PDOEngine::getInstance()->getCharset();
        $connection = PDOEngine::getInstance()->getConnection();

        if ($driver == 'mysql' && $charset) {
            $connection->exec(sprintf("SET NAMES '%s'", $charset));
        } elseif ($driver == 'pgsql' && $charset) {
            $connection->exec(sprintf("SET CLIENT_ENCODING TO '%s'", $charset));
        } elseif ($driver == 'sqlite') {
            $connection->query('PRAGMA foreign_keys = ON');
        }
    }
}
