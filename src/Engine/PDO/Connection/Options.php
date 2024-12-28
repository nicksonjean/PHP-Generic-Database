<?php

namespace GenericDatabase\Engine\PDO\Connection;

use PDO;
use GenericDatabase\Helpers\CustomException;
use GenericDatabase\Engine\PDOConnection;

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
        if (!in_array(PDOConnection::getInstance()->getDriver(), PDO::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                PDOConnection::getInstance()->getDriver(),
                implode(', ', PDO::getAvailableDrivers())
            );
            throw new CustomException($message);
        }

        $options += [PDO::ATTR_ERRMODE => (PDOConnection::getInstance()->getException())
            ? PDO::ERRMODE_WARNING
            : PDO::ERRMODE_SILENT];
        switch (PDOConnection::getInstance()->getDriver()) {
            case 'mysql':
                if (PDOConnection::getInstance()->getCharset()) {
                    $options += [PDO::MYSQL_ATTR_INIT_COMMAND => sprintf(
                        "SET NAMES '%s';",
                        PDOConnection::getInstance()->getCharset()
                    )];
                }
                $options += [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true];
                break;
                // Fall-through intentional
            case 'pgsql':
                $options += [PDO::ATTR_AUTOCOMMIT => true];
                break;
            case 'sqlsrv':
                if (PDOConnection::getInstance()->getCharset()) {
                    $options += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8];
                } else {
                    $options += [PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_SYSTEM];
                }
                break;
            case 'sqlite':
                unset(PDOConnection::getInstance()->user, PDOConnection::getInstance()->password);
                break;
            default:
                $options += [PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING];
        }
        self::$options = $options;
    }

    /**
     * This method is responsible for set options after connect in database,
     * more information's in https://www.php.net/manual/en/pdo.configuration.php
     *
     * @return void
     */
    public static function define(): void
    {
        $driver = PDOConnection::getInstance()->getDriver();
        $charset = PDOConnection::getInstance()->getCharset();
        $connection = PDOConnection::getInstance()->getConnection();

        if ($driver == 'mysql' && $charset) {
            $connection->exec(sprintf("SET NAMES '%s'", $charset));
        } elseif ($driver == 'pgsql' && $charset) {
            $connection->exec(sprintf("SET CLIENT_ENCODING TO '%s'", $charset));
        } elseif ($driver == 'sqlite') {
            $connection->query('PRAGMA foreign_keys = ON');
        }
    }
}
