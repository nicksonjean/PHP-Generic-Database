<?php

namespace GenericDatabase\Engine\PDO\Connection\DSN;

use PDO;
use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IDSN;
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

    /**
     * @throws Exceptions
     */
    public function parse(): string|Exceptions
    {
        if (!extension_loaded('pdo')) {
            throw new Exceptions("Invalid or not loaded 'pdo' extension in PHP.ini settings");
        }

        if (!in_array($this->get('driver'), PDO::getAvailableDrivers())) {
            throw new Exceptions(sprintf(
                "Driver '%s' is invalid, set the driver property with one of these options: '%s'",
                $this->get('driver'),
                implode(', ', PDO::getAvailableDrivers())
            ));
        }

        return match ($this->get('driver')) {
            'mysql' => $this->handleMySQL(),
            'pgsql' => $this->handlePostgres(),
            'oci' => $this->handleOci(),
            'dblib', 'sybase' => $this->handleSybase(),
            'sqlsrv' => $this->handleSqlsrv(),
            'mssql' => $this->handleMssql(),
            'ibase', 'firebird' => $this->handleFirebird(),
            'sqlite' => $this->handleSQLite(),
            default => $this->handleDefault(),
        };
    }

    private function handleMySQL(): string
    {
        $result = vsprintf(
            "%s:host=%s;dbname=%s;port=%s;charset=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('database'),
                $this->get('port'),
                $this->get('charset')
            ]
        );

        $this->set(
            'dsn',
            $result
        );
        return $result;
    }

    private function handlePostgres(): string
    {
        $result = fn(bool $default = false) => vsprintf(
            "%s:host=%s;dbname=%s;port=%s;user=%s;password=%s;options='--client_encoding=%s'",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('database'),
                $this->get('port'),
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
        $result = (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ?
            vsprintf(
                "%s:host=%s:%s/%s;charset=%s",
                [
                    $this->get('driver'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('charset')
                ]
            ) :
            vsprintf(
                "%s:dbname=%s:%s/%s;charset=%s",
                [
                    $this->get('driver'),
                    $this->get('host'),
                    $this->get('port'),
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

    private function handleSybase(): string
    {
        $result = vsprintf(
            "%s:host=%s:%s/%s;charset=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('port'),
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

    private function handleSqlsrv(): string
    {
        $result = vsprintf(
            "%s:server=%s,%s;database=%s;TrustServerCertificate=yes;Encrypt=no;",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database')
            ]
        );

        $this->set(
            'dsn',
            $result
        );
        return $result;
    }

    private function handleMssql(): string
    {
        $this->set('driver', (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'mssql' : 'dblib');

        $result = (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ?
            vsprintf(
                "%s:server=%s,%s;database=%s",
                [
                    $this->get('driver'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                ]
            ) :
            vsprintf(
                "%s:host=%s:%s/%s;charset=%s",
                [
                    $this->get('driver'),
                    $this->get('host'),
                    $this->get('port'),
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
    private function handleFirebird(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }
        $result = vsprintf(
            "%s:dbname=%s/%s:%s;charset=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('port'),
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
    private function handleSQLite(): string
    {
        $result = null;
        if (!Path::isAbsolute($this->get('database')) && $this->get('database') !== 'memory') {
            $this->set('database', Path::toAbsolute($this->get('database')));
            $result = vsprintf(
                "%s:%s",
                [
                    $this->get('driver'),
                    $this->get('database')
                ]
            );
        } else {
            $result = vsprintf(
                "%s::%s:",
                [
                    $this->get('driver'),
                    $this->get('database')
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
                "%s:%s",
                [
                    $this->get('driver'),
                    $this->get('database')
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

