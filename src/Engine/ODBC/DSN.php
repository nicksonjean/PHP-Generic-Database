<?php

namespace GenericDatabase\Engine\ODBC;

use AllowDynamicProperties;
use GenericDatabase\Engine\ODBCEngine;
use GenericDatabase\Engine\ODBC\ODBC;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parseDsn(): string|CustomException
    {
        if (!extension_loaded('odbc')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'odbc',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        // if (!in_array(ODBCEngine::getInstance()->getDriver(), ODBC::getAvailableDrivers())) {
        //     $message = sprintf(
        //         "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
        //         ODBCEngine::getInstance()->getDriver(),
        //         implode(', ', ODBC::getAvailableDrivers())
        //     );
        //     throw new CustomException($message);
        // }

        $result = null;
        switch (ODBCEngine::getInstance()->getDriver()) {
            case 'text':
                if (!Path::isAbsolute(ODBCEngine::getInstance()->getDatabase())) {
                    ODBCEngine::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ));
                }
                $result = sprintf(
                    "Driver={%s};DBQ=%s;Charset=%s;Extensions=asc,csv,tab,txt;FMT=Delimited(;);HDR=YES;",
                    ODBC::getAliasByDriver(
                        ODBCEngine::getInstance()->getDriver(),
                        (PHP_INT_SIZE === 4) ? 'x86' : 'x64'
                    ),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'excel':
                if (!Path::isAbsolute(ODBCEngine::getInstance()->getDatabase())) {
                    ODBCEngine::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ));
                }
                $file = pathinfo(ODBCEngine::getInstance()->getDatabase());
                $result = sprintf(
                    "Driver={%s};DriverID=" .
                        ($file['extension'] === 'xls' ? "790" : "1046") .
                        ";DBQ=%s;DefaultDir=%s;Charset=%s;Extensions=" .
                        ($file['extension'] === 'xls' ? "xls" : "xls,xlsx,xlsm,xlsb") .
                        ";ImportMixedTypes=Text;",
                    ODBC::getAliasByDriver(
                        ODBCEngine::getInstance()->getDriver(),
                        ($file['extension'] === 'xls') ? 'xls' : 'xlsx'
                    ),
                    ODBCEngine::getInstance()->getDatabase(),
                    dirname(ODBCEngine::getInstance()->getDatabase()),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'access':
                if (!Path::isAbsolute(ODBCEngine::getInstance()->getDatabase())) {
                    ODBCEngine::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ));
                }
                $file = pathinfo(ODBCEngine::getInstance()->getDatabase());
                $result = sprintf(
                    "Driver={%s};DBQ=%s;UID=%s;PWD=%s;Charset=%s;ExtendedAnsiSQL=1;",
                    ODBC::getAliasByDriver(
                        ODBCEngine::getInstance()->getDriver(),
                        ($file['extension'] === 'mdb') ? 'mdb' : 'accdb'
                    ),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getUser(),
                    ODBCEngine::getInstance()->getPassword(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'ibase':
            case 'firebird':
                if (!Path::isAbsolute(ODBCEngine::getInstance()->getDatabase())) {
                    ODBCEngine::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ));
                }
                $result = sprintf(
                    "Driver={%s};UID=%s;PWD=%s;DBNAME=%s/%s:%s;Charset=%s;AUTOQUOTED=YES;",
                    ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                    ODBCEngine::getInstance()->getUser(),
                    ODBCEngine::getInstance()->getPassword(),
                    ODBCEngine::getInstance()->getHost(),
                    ODBCEngine::getInstance()->getPort(),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'sqlite':
                if (
                    !Path::isAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ) && ODBCEngine::getInstance()->getDatabase() != 'memory'
                ) {
                    ODBCEngine::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ));
                    $result = sprintf(
                        "Driver={%s};Database=%s;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                        ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                        ODBCEngine::getInstance()->getDatabase(),
                        ODBCEngine::getInstance()->getCharset()
                    );
                } else {
                    $result = sprintf(
                        "Driver={%s};Database=:%s:;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                        ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                        ODBCEngine::getInstance()->getDatabase(),
                        ODBCEngine::getInstance()->getCharset()
                    );
                }
                break;
            case 'oci':
                $result = sprintf(
                    "Driver={%s};Server=%s:%s/%s;UID=%s;PWD=%s;Charset=%s;",
                    ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                    ODBCEngine::getInstance()->getHost(),
                    ODBCEngine::getInstance()->getPort(),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getUser(),
                    ODBCEngine::getInstance()->getPassword(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'pgsql':
                $result = sprintf(
                    "Driver={%s};Server=%s;Port=%s;Database=%s;UID=%s;PWD=%s;Charset=%s;",
                    ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                    ODBCEngine::getInstance()->getHost(),
                    ODBCEngine::getInstance()->getPort(),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getUser(),
                    ODBCEngine::getInstance()->getPassword(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'dblib':
            case 'mssql':
            case 'sybase':
            case 'sqlsrv':
                $result = sprintf(
                    "Driver={%s};Server=%s,%s;Database=%s;UID=%s;PWD=%s;Trusted_Connection=YES;Charset=%s;",
                    ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                    ODBCEngine::getInstance()->getHost(),
                    ODBCEngine::getInstance()->getPort(),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getUser(),
                    ODBCEngine::getInstance()->getPassword(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;

            case 'mysql':
                $result = sprintf(
                    "Driver={%s};Server=%s;Port=%s;Database=%s;User=%s;Password=%s;Charset=%s;Option=3;",
                    ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                    ODBCEngine::getInstance()->getHost(),
                    ODBCEngine::getInstance()->getPort(),
                    ODBCEngine::getInstance()->getDatabase(),
                    ODBCEngine::getInstance()->getUser(),
                    ODBCEngine::getInstance()->getPassword(),
                    ODBCEngine::getInstance()->getCharset()
                );
                break;
            default:
                if (
                    !Path::isAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ) && ODBCEngine::getInstance()->getDatabase() != 'memory'
                ) {
                    ODBCEngine::getInstance()->setDatabase(Path::toAbsolute(
                        ODBCEngine::getInstance()->getDatabase()
                    ));
                    $result = sprintf(
                        "Driver={%s};Database=:%s:;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                        ODBC::getAliasByDriver(ODBCEngine::getInstance()->getDriver(), null),
                        ODBCEngine::getInstance()->getDatabase(),
                        ODBCEngine::getInstance()->getCharset()
                    );
                }
        }
        ODBCEngine::getInstance()->setDsn($result);
        return $result;
    }
}
