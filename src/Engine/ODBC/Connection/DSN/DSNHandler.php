<?php

namespace GenericDatabase\Engine\ODBC\Connection\DSN;

use GenericDatabase\Engine\ODBC\Connection\ODBC;
use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\DSN\IDSN;
use GenericDatabase\Interfaces\IConnection;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    private static array $dsnFile;

    private IConnection $connection;

    public function __construct(IConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getInstance(): IConnection
    {
        return $this->connection;
    }

    private function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    private function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    public function load(): array
    {
        if (!isset(self::$dsnFile)) {
            self::$dsnFile = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'DSN.json'), true);
        }
        return self::$dsnFile;
    }

    /**
     * @throws Exceptions
     */
    public function parse(): string|Exceptions
    {
        if (!extension_loaded('odbc')) {
            throw new Exceptions("Invalid or not loaded 'odbc' extension in PHP.ini settings");
        }

        if (!in_array($this->get('driver'), ODBC::getAvailableDrivers())) {
            throw new Exceptions(sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                $this->get('driver'),
                implode(', ', ODBC::getAvailableDrivers())
            ));
        }

        $this->set(
            'dsn',
            match ($this->get('driver')) {
                'text' => $this->handleText(),
                'excel' => $this->handleExcel(),
                'access' => $this->handleAccess(),
                'mysql' => $this->handleMySQL(),
                'pgsql' => $this->handlePostgres(),
                'oci' => $this->handleOci(),
                'dblib', 'sybase', 'sqlsrv', 'mssql' => $this->handleSqlsrv(),
                'ibase', 'firebird' => $this->handleFirebird(),
                'sqlite' => $this->handleSQLite(),
                default => $this->handleDefault(),
            }
        );
        return $this->get('dsn');
    }

    private function handleText(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        return vsprintf(
            "Driver={%s};DBQ=%s;Charset=%s;Extensions=asc,csv,tab,txt;FMT=Delimited(;);HDR=YES;",
            [
                ODBC::getAliasByDriver($this->get('driver'), (PHP_INT_SIZE === 4) ? 'x86' : 'x64'),
                $this->get('database'),
                $this->get('charset')
            ]
        );
    }

    private function handleExcel(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $file = pathinfo($this->get('database'));
        return vsprintf(
            "Driver={%s};DriverID=" . ($file['extension'] === 'xls' ? "790" : "1046") . ";DBQ=%s;DefaultDir=%s;Charset=%s;Extensions=" . ($file['extension'] === 'xls' ? "xls" : "xls,xlsx,xlsm,xlsb") . ";ImportMixedTypes=Text;",
            [
                ODBC::getAliasByDriver($this->get('driver'), ($file['extension'] === 'xls') ? 'xls' : 'xlsx'),
                $this->get('database'),
                dirname($this->get('database')),
                $this->get('charset')
            ]
        );
    }

    private function handleAccess(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $file = pathinfo($this->get('database'));
        $extension = ($file['extension'] === 'mdb') ? 'mdb' : 'accdb';
        return vsprintf(
            "Driver={%s};DBQ=%s;UID=%s;PWD=%s;Charset=%s;ExtendedAnsiSQL=1;",
            [
                ODBC::getAliasByDriver($this->get('driver'), (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? $extension : null),
                $this->get('database'),
                $this->get('user'),
                $this->get('password'),
                $this->get('charset')
            ]
        );
    }

    private function handleMySQL(): string
    {
        return vsprintf(
            "Driver={%s};Server=%s;Port=%s;Database=%s;User=%s;Password=%s;Charset=%s;Option=3;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $this->get('password'),
                $this->get('charset')
            ]
        );
    }

    private function handlePostgres(): string
    {
        return vsprintf(
            "Driver={%s};Server=%s;Port=%s;Database=%s;UID=%s;PWD=%s;Charset=%s;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $this->get('password'),
                $this->get('charset')
            ]
        );
    }

    private function handleOci(): string
    {
        $server = (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'Server' : 'DBQ';
        return vsprintf(
            "Driver={%s};$server=%s:%s/%s;UID=%s;PWD=%s;Charset=%s;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $this->get('password'),
                $this->get('charset')
            ]
        );
    }

    private function handleSqlsrv(): string
    {
        return vsprintf(
            "Driver={%s};Server=%s,%s;Database=%s;UID=%s;PWD=%s;Charset=%s;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $this->get('password'),
                $this->get('charset')
            ]
        );
    }

    private function handleFirebird(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }
        return vsprintf(
            "Driver={%s};UID=%s;PWD=%s;DBNAME=%s/%s:%s;Charset=%s;AUTOQUOTED=YES;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('user'),
                $this->get('password'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('charset')
            ]
        );
    }

    private function handleSQLite(): string
    {
        if (!Path::isAbsolute($this->get('database')) && $this->get('database') !== 'memory') {
            $this->set('database', Path::toAbsolute($this->get('database')));
            $result = vsprintf(
                "Driver={%s};Database=%s;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                [
                    ODBC::getAliasByDriver($this->get('driver')),
                    $this->get('database'),
                    $this->get('charset')
                ]
            );
        } else {
            $result = vsprintf(
                "Driver={%s};Database=:%s:;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                [
                    ODBC::getAliasByDriver($this->get('driver')),
                    $this->get('database'),
                    $this->get('charset')
                ]
            );
        }
        return $result;
    }

    private function handleDefault(): string
    {
        if (!Path::isAbsolute($this->get('database')) && $this->get('database') !== 'memory') {
            $this->set('database', Path::toAbsolute($this->get('database')));
            $result = vsprintf(
                "Driver={%s};Database=:%s:;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;Charset=%s",
                [
                    ODBC::getAliasByDriver($this->get('driver')),
                    $this->get('database'),
                    $this->get('charset')
                ]
            );
        }
        return $result;
    }
}
