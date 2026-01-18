<?php

namespace GenericDatabase\Engine\ODBC\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Engine\ODBC\Connection\ODBC;
use GenericDatabase\Generic\Connection\SensitiveValue;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    protected static IConnection $instance;

    public function __construct(IConnection $instance)
    {
        self::$instance = $instance;
    }

    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    private static array $dsnFile;

    public static function load(): array
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

        return match ($this->get('driver')) {
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
        };
    }

    /**
     * @throws Exceptions
     */
    private function handleText(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $result = vsprintf(
            "Driver={%s};DBQ=%s;Charset=%s;Extensions=asc,csv,tab,txt;FMT=Delimited(;);HDR=YES;",
            [
                ODBC::getAliasByDriver($this->get('driver'), (PHP_INT_SIZE === 4) ? 'x86' : 'x64'),
                $this->get('database'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result
        );
        return $result;
    }

    /**
     * @throws Exceptions
     */
    private function handleExcel(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $file = pathinfo($this->get('database'));
        $result = vsprintf(
            "Driver={%s};DriverID=" . ($file['extension'] === 'xls' ? "790" : "1046") . ";DBQ=%s;DefaultDir=%s;Charset=%s;Extensions=" . ($file['extension'] === 'xls' ? "xls" : "xls,xlsx,xlsm,xlsb") . ";ImportMixedTypes=Text;",
            [
                ODBC::getAliasByDriver($this->get('driver'), ($file['extension'] === 'xls') ? 'xls' : 'xlsx'),
                $this->get('database'),
                dirname($this->get('database')),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result
        );
        return $result;
    }

    /**
     * @throws Exceptions
     */
    private function handleAccess(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $file = pathinfo($this->get('database'));
        $extension = ($file['extension'] === 'mdb') ? 'mdb' : 'accdb';
        $result = fn(bool $default = false) => vsprintf(
            "Driver={%s};DBQ=%s;UID=%s;PWD=%s;Charset=%s;ExtendedAnsiSQL=1;",
            [
                ODBC::getAliasByDriver($this->get('driver'), (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? $extension : null),
                $this->get('database'),
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result(true)
        );
        return $result(false);
    }

    private function handleMySQL(): string
    {
        $result = fn(bool $default = false) => vsprintf(
            "Driver={%s};Server=%s;Port=%s;Database=%s;User=%s;Password=%s;Charset=%s;Option=3;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result(true)
        );
        return $result(false);
    }

    private function handlePostgres(): string
    {
        $result = fn(bool $default = false) => vsprintf(
            "Driver={%s};Server=%s;Port=%s;Database=%s;UID=%s;PWD=%s;Charset=%s;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result(true)
        );
        return $result(false);
    }

    private function handleOci(): string
    {

        $result = fn(bool $default = false) => vsprintf(
            "Driver={%s};%s=%s:%s/%s;UID=%s;PWD=%s;Charset=%s;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'Server' : 'DBQ',
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result(true)
        );
        return $result(false);
    }

    private function handleSqlsrv(): string
    {
        $result = fn(bool $default = false) => vsprintf(
            "Driver={%s};Server=%s,%s;Database=%s;UID=%s;PWD=%s;Charset=%s;TrustServerCertificate=yes;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result(true)
        );
        return $result(false);
    }

    /**
     * @throws Exceptions
     */
    private function handleFirebird(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }
        $result = fn(bool $default = false) => vsprintf(
            "Driver={%s};UID=%s;PWD=%s;DBNAME=%s/%s:%s;Charset=%s;AUTOQUOTED=YES;",
            [
                ODBC::getAliasByDriver($this->get('driver')),
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result(true)
        );
        return $result(false);
    }

    /**
     * @throws Exceptions
     */
    private function handleSQLite(): string
    {
        $result = null;
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

        $this->set(
            'dsn',
            $result
        );
        return $result;
    }

    /**
     * @throws Exceptions
     */
    private function handleDefault(): string
    {
        $result = null;
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

        $this->set(
            'dsn',
            $result
        );
        return $result;
    }
}

