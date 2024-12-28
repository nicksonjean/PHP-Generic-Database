<?php

namespace GenericDatabase\Engine\PDO\Connection;

use PDO;
use AllowDynamicProperties;
use GenericDatabase\Engine\PDOConnection;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('pdo')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'pdo',
                'PHP.ini'
            );
            throw new CustomException($message);
        }
        if (!in_array(PDOConnection::getInstance()->getDriver(), PDO::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                PDOConnection::getInstance()->getDriver(),
                implode(', ', PDO::getAvailableDrivers())
            );
            throw new CustomException($message);
        }
        $result = null;
        switch (PDOConnection::getInstance()->getDriver()) {
            case 'mysql':
                $result = sprintf(
                    "%s:host=%s;dbname=%s;port=%s;charset=%s",
                    PDOConnection::getInstance()->getDriver(),
                    PDOConnection::getInstance()->getHost(),
                    PDOConnection::getInstance()->getDatabase(),
                    PDOConnection::getInstance()->getPort(),
                    PDOConnection::getInstance()->getCharset()
                );
                break;

            case 'pgsql':
                $result = sprintf(
                    "%s:host=%s;dbname=%s;port=%s;user=%s;password=%s;options='--client_encoding=%s'",
                    PDOConnection::getInstance()->getDriver(),
                    PDOConnection::getInstance()->getHost(),
                    PDOConnection::getInstance()->getDatabase(),
                    PDOConnection::getInstance()->getPort(),
                    PDOConnection::getInstance()->getUser(),
                    PDOConnection::getInstance()->getPassword(),
                    PDOConnection::getInstance()->getCharset()
                );
                break;

            case 'oci':
                if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $result = sprintf(
                        "%s:host=%s:%s/%s;charset=%s",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getHost(),
                        PDOConnection::getInstance()->getPort(),
                        PDOConnection::getInstance()->getDatabase(),
                        PDOConnection::getInstance()->getCharset()
                    );
                } else {
                    $result = sprintf(
                        "%s:dbname=%s:%s/%s;charset=%s",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getHost(),
                        PDOConnection::getInstance()->getPort(),
                        PDOConnection::getInstance()->getDatabase(),
                        PDOConnection::getInstance()->getCharset()
                    );
                }
                break;

            case 'dblib':
            case 'sybase':
                $result = sprintf(
                    "%s:host=%s:%s/%s;charset=%s",
                    PDOConnection::getInstance()->getDriver(),
                    PDOConnection::getInstance()->getHost(),
                    PDOConnection::getInstance()->getPort(),
                    PDOConnection::getInstance()->getDatabase(),
                    PDOConnection::getInstance()->getCharset()
                );
                break;

            case 'sqlsrv':
                $result = sprintf(
                    "%s:server=%s,%s;database=%s",
                    PDOConnection::getInstance()->getDriver(),
                    PDOConnection::getInstance()->getHost(),
                    PDOConnection::getInstance()->getPort(),
                    PDOConnection::getInstance()->getDatabase()
                );
                break;

            case 'mssql':
                if (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    PDOConnection::getInstance()->setDriver('sqlsrv');
                    $result = sprintf(
                        "%s:server=%s,%s;database=%s",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getHost(),
                        PDOConnection::getInstance()->getPort(),
                        PDOConnection::getInstance()->getDatabase()
                    );
                } else {
                    PDOConnection::getInstance()->setDriver('dblib');
                    $result = sprintf(
                        "%s:host=%s:%s/%s;charset=%s",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getHost(),
                        PDOConnection::getInstance()->getPort(),
                        PDOConnection::getInstance()->getDatabase(),
                        PDOConnection::getInstance()->getCharset()
                    );
                }
                break;

            case 'ibase':
            case 'firebird':
                if (!Path::isAbsolute(PDOConnection::getInstance()->getDatabase())) {
                    PDOConnection::getInstance()->setDatabase(Path::toAbsolute(
                        PDOConnection::getInstance()->getDatabase()
                    ));
                }
                $result = sprintf(
                    "%s:dbname=%s/%s:%s;charset=%s",
                    PDOConnection::getInstance()->getDriver(),
                    PDOConnection::getInstance()->getHost(),
                    PDOConnection::getInstance()->getPort(),
                    PDOConnection::getInstance()->getDatabase(),
                    PDOConnection::getInstance()->getCharset()
                );
                break;

            case 'sqlite':
                if (
                    !Path::isAbsolute(
                        PDOConnection::getInstance()->getDatabase()
                    ) && PDOConnection::getInstance()->getDatabase() != 'memory'
                ) {
                    PDOConnection::getInstance()->setDatabase(Path::toAbsolute(
                        PDOConnection::getInstance()->getDatabase()
                    ));
                    $result = sprintf(
                        "%s:%s",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getDatabase()
                    );
                } else {
                    $result = sprintf(
                        "%s::%s:",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getDatabase()
                    );
                }
                break;
            default:
                if (
                    !Path::isAbsolute(
                        PDOConnection::getInstance()->getDatabase()
                    ) && PDOConnection::getInstance()->getDatabase() != 'memory'
                ) {
                    PDOConnection::getInstance()->setDatabase(Path::toAbsolute(
                        PDOConnection::getInstance()->getDatabase()
                    ));
                    $result = sprintf(
                        "%s:%s",
                        PDOConnection::getInstance()->getDriver(),
                        PDOConnection::getInstance()->getDatabase()
                    );
                }
        }
        PDOConnection::getInstance()->setDsn($result);
        return $result;
    }
}
