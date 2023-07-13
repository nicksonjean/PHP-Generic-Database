<?php

namespace GenericDatabase\Engine\PDO;

use PDO;
use AllowDynamicProperties;
use GenericDatabase\Traits\Path;
use GenericDatabase\Engine\PDOEngine;
use GenericDatabase\Helpers\GenericException;

#[AllowDynamicProperties]
class DSN
{
    public static function parseDsn(): string|GenericException
    {
        if (!in_array(PDOEngine::getInstance()->getDriver(), (array) PDO::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                PDOEngine::getInstance()->getDriver(),
                implode(', ', (array) PDO::getAvailableDrivers())
            );
            throw new GenericException($message);
        }
        $result = null;
        switch (PDOEngine::getInstance()->getDriver()) {
            case 'mysql':
                $result = sprintf(
                    "%s:host=%s;dbname=%s;port=%s;charset=%s",
                    PDOEngine::getInstance()->getDriver(),
                    PDOEngine::getInstance()->getHost(),
                    PDOEngine::getInstance()->getDatabase(),
                    PDOEngine::getInstance()->getPort(),
                    PDOEngine::getInstance()->getCharset()
                );
                break;

            case 'pgsql':
                $result = sprintf(
                    "%s:host=%s;dbname=%s;port=%s;user=%s;password=%s;options='--client_encoding=%s'",
                    PDOEngine::getInstance()->getDriver(),
                    PDOEngine::getInstance()->getHost(),
                    PDOEngine::getInstance()->getDatabase(),
                    PDOEngine::getInstance()->getPort(),
                    PDOEngine::getInstance()->getUser(),
                    PDOEngine::getInstance()->getPassword(),
                    PDOEngine::getInstance()->getCharset()
                );
                break;

            case 'oci':
            case 'dblib':
            case 'sybase':
                $result = sprintf(
                    "%s:host=%s:%s/%s;charset=%s",
                    PDOEngine::getInstance()->getDriver(),
                    PDOEngine::getInstance()->getHost(),
                    PDOEngine::getInstance()->getPort(),
                    PDOEngine::getInstance()->getDatabase(),
                    PDOEngine::getInstance()->getCharset()
                );
                break;

            case 'sqlsrv':
                $result = sprintf(
                    "%s:server=%s,%s;database=%s",
                    PDOEngine::getInstance()->getDriver(),
                    PDOEngine::getInstance()->getHost(),
                    PDOEngine::getInstance()->getPort(),
                    PDOEngine::getInstance()->getDatabase()
                );
                break;

            case 'mssql':
                if (PHP_OS == 'WIN') {
                    PDOEngine::getInstance()->setDriver('sqlsrv');
                    $result = sprintf(
                        "%s:server=%s,%s;database=%s",
                        PDOEngine::getInstance()->getDriver(),
                        PDOEngine::getInstance()->getHost(),
                        PDOEngine::getInstance()->getPort(),
                        PDOEngine::getInstance()->getDatabase()
                    );
                } else {
                    PDOEngine::getInstance()->setDriver('dblib');
                    $result = sprintf(
                        "%s:host=%s:%s/%s;charset=%s",
                        PDOEngine::getInstance()->getDriver(),
                        PDOEngine::getInstance()->getHost(),
                        PDOEngine::getInstance()->getPort(),
                        PDOEngine::getInstance()->getDatabase(),
                        PDOEngine::getInstance()->getCharset()
                    );
                }
                break;

            case 'ibase':
            case 'firebird':
                if (!Path::isAbsolute(PDOEngine::getInstance()->getDatabase())) {
                    PDOEngine::getInstance()->setDatabase(Path::toAbsolute(PDOEngine::getInstance()->getDatabase()));
                }
                $result = sprintf(
                    "%s:dbname=%s/%s:%s;charset=%s",
                    PDOEngine::getInstance()->getDriver(),
                    PDOEngine::getInstance()->getHost(),
                    PDOEngine::getInstance()->getPort(),
                    PDOEngine::getInstance()->getDatabase(),
                    PDOEngine::getInstance()->getCharset()
                );
                break;

            case 'sqlite':
                if (
                    !Path::isAbsolute(
                        PDOEngine::getInstance()->getDatabase()
                    ) && PDOEngine::getInstance()->getDatabase() !== 'memory'
                ) {
                    PDOEngine::getInstance()->setDatabase(Path::toAbsolute(PDOEngine::getInstance()->getDatabase()));
                    $result = sprintf(
                        "%s:%s",
                        PDOEngine::getInstance()->getDriver(),
                        PDOEngine::getInstance()->getDatabase()
                    );
                } else {
                    $result = sprintf(
                        "%s::%s:",
                        PDOEngine::getInstance()->getDriver(),
                        PDOEngine::getInstance()->getDatabase()
                    );
                }
                break;
            default:
                if (
                    !Path::isAbsolute(
                        PDOEngine::getInstance()->getDatabase()
                    ) && PDOEngine::getInstance()->getDatabase() !== 'memory'
                ) {
                    PDOEngine::getInstance()->setDatabase(Path::toAbsolute(PDOEngine::getInstance()->getDatabase()));
                    $result = sprintf(
                        "%s:%s",
                        PDOEngine::getInstance()->getDriver(),
                        PDOEngine::getInstance()->getDatabase()
                    );
                }
        }
        PDOEngine::getInstance()->setDsn((string) $result);
        return $result;
    }
}
