<?php

namespace GenericDatabase\Engine\ODBC\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\ODBCConnection;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    private static array $dsnFile;

    public static function load(): array
    {
        if (!isset(self::$dsnFile)) {
            $dsep = DIRECTORY_SEPARATOR;
            $json = dirname(__DIR__, 3) . $dsep . 'Helpers' . $dsep . 'ODBC' . $dsep . 'DSN.json';
            self::$dsnFile = json_decode(file_get_contents($json), true);
        }
        return self::$dsnFile;
    }

    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('odbc')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'odbc',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        if (!in_array(ODBCConnection::getInstance()->getDriver(), ODBC::getAvailableDrivers())) {
            $message = sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                ODBCConnection::getInstance()->getDriver(),
                implode(', ', ODBC::getAvailableDrivers())
            );
            throw new CustomException($message);
        }

        $result = null;
        switch (ODBCConnection::getInstance()->getDriver()) {
            case 'text':
                if (!Path::isAbsolute(ODBCConnection::getInstance()->getDatabase())) {
                    ODBCConnection::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ));
                }
                $result = sprintf(
                    "Driver={%s};DBQ=%s;Charset=%s;Extensions=asc,csv,tab,txt;FMT=Delimited(;);HDR=YES;",
                    ODBC::getAliasByDriver(
                        ODBCConnection::getInstance()->getDriver(),
                        (PHP_INT_SIZE === 4) ? 'x86' : 'x64'
                    ),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'excel':
                if (!Path::isAbsolute(ODBCConnection::getInstance()->getDatabase())) {
                    ODBCConnection::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ));
                }
                $file = pathinfo(ODBCConnection::getInstance()->getDatabase());
                $result = sprintf(
                    "Driver={%s};DriverID=" .
                    ($file['extension'] === 'xls' ? "790" : "1046") .
                    ";DBQ=%s;DefaultDir=%s;Charset=%s;Extensions=" .
                    ($file['extension'] === 'xls' ? "xls" : "xls,xlsx,xlsm,xlsb") .
                    ";ImportMixedTypes=Text;",
                    ODBC::getAliasByDriver(
                        ODBCConnection::getInstance()->getDriver(),
                        ($file['extension'] === 'xls') ? 'xls' : 'xlsx'
                    ),
                    ODBCConnection::getInstance()->getDatabase(),
                    dirname(ODBCConnection::getInstance()->getDatabase()),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'access':
                if (!Path::isAbsolute(ODBCConnection::getInstance()->getDatabase())) {
                    ODBCConnection::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ));
                }
                $file = pathinfo(ODBCConnection::getInstance()->getDatabase());
                $extension = ($file['extension'] === 'mdb') ? 'mdb' : 'accdb';
                $result = sprintf(
                    "Driver={%s};DBQ=%s;UID=%s;PWD=%s;Charset=%s;ExtendedAnsiSQL=1;",
                    ODBC::getAliasByDriver(
                        ODBCConnection::getInstance()->getDriver(),
                        (PHP_OS === 'Windows') ? $extension : null
                    ),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getUser(),
                    ODBCConnection::getInstance()->getPassword(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'ibase':
            case 'firebird':
                if (!Path::isAbsolute(ODBCConnection::getInstance()->getDatabase())) {
                    ODBCConnection::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ));
                }
                $result = sprintf(
                    "Driver={%s};UID=%s;PWD=%s;DBNAME=%s/%s:%s;Charset=%s;AUTOQUOTED=YES;",
                    ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                    ODBCConnection::getInstance()->getUser(),
                    ODBCConnection::getInstance()->getPassword(),
                    ODBCConnection::getInstance()->getHost(),
                    ODBCConnection::getInstance()->getPort(),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'sqlite':
                if (
                    !Path::isAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ) && ODBCConnection::getInstance()->getDatabase() != 'memory'
                ) {
                    ODBCConnection::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ));
                    $result = sprintf(
                        "Driver={%s};Database=%s;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                        ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                        ODBCConnection::getInstance()->getDatabase(),
                        ODBCConnection::getInstance()->getCharset()
                    );
                } else {
                    $result = sprintf(
                        "Driver={%s};Database=:%s:;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                        ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                        ODBCConnection::getInstance()->getDatabase(),
                        ODBCConnection::getInstance()->getCharset()
                    );
                }
                break;
            case 'oci':
                $result = sprintf(
                    "Driver={%s};Server=%s:%s/%s;UID=%s;PWD=%s;Charset=%s;",
                    ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                    ODBCConnection::getInstance()->getHost(),
                    ODBCConnection::getInstance()->getPort(),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getUser(),
                    ODBCConnection::getInstance()->getPassword(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'pgsql':
                $result = sprintf(
                    "Driver={%s};Server=%s;Port=%s;Database=%s;UID=%s;PWD=%s;Charset=%s;",
                    ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                    ODBCConnection::getInstance()->getHost(),
                    ODBCConnection::getInstance()->getPort(),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getUser(),
                    ODBCConnection::getInstance()->getPassword(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'dblib':
            case 'mssql':
            case 'sybase':
            case 'sqlsrv':
                $result = sprintf(
                    "Driver={%s};Server=%s,%s;Database=%s;UID=%s;PWD=%s;Charset=%s;",
                    ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                    ODBCConnection::getInstance()->getHost(),
                    ODBCConnection::getInstance()->getPort(),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getUser(),
                    ODBCConnection::getInstance()->getPassword(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;

            case 'mysql':
                $result = sprintf(
                    "Driver={%s};Server=%s;Port=%s;Database=%s;User=%s;Password=%s;Charset=%s;Option=3;",
                    ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                    ODBCConnection::getInstance()->getHost(),
                    ODBCConnection::getInstance()->getPort(),
                    ODBCConnection::getInstance()->getDatabase(),
                    ODBCConnection::getInstance()->getUser(),
                    ODBCConnection::getInstance()->getPassword(),
                    ODBCConnection::getInstance()->getCharset()
                );
                break;
            default:
                if (
                    !Path::isAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ) && ODBCConnection::getInstance()->getDatabase() != 'memory'
                ) {
                    ODBCConnection::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCConnection::getInstance()->getDatabase()
                    ));
                    $result = sprintf(
                        "Driver={%s};Database=:%s:;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                        ODBC::getAliasByDriver(ODBCConnection::getInstance()->getDriver()),
                        ODBCConnection::getInstance()->getDatabase(),
                        ODBCConnection::getInstance()->getCharset()
                    );
                }
        }
        ODBCConnection::getInstance()->setDsn($result);
        return $result;
    }
}
