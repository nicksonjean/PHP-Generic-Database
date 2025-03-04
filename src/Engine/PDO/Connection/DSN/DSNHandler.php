<?php

namespace GenericDatabase\Engine\PDO\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IDSN;
use PDO;

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

        $this->set(
            'dsn',
            match ($this->get('driver')) {
                'mysql' => $this->handleMySQL(),
                'pgsql' => $this->handlePostgres(),
                'oci' => $this->handleOci(),
                'dblib', 'sybase' => $this->handleSybase(),
                'sqlsrv' => $this->handleSqlsrv(),
                'mssql' => $this->handleMssql(),
                'ibase', 'firebird' => $this->handleFirebird(),
                'sqlite' => $this->handleSQLite(),
                default => $this->handleDefault(),
            }
        );
        return $this->get('dsn');
    }

    private function handleMySQL(): string
    {
        return vsprintf(
            "%s:host=%s;dbname=%s;port=%s;charset=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('database'),
                $this->get('port'),
                $this->get('charset')
            ]
        );
    }

    private function handlePostgres(): string
    {
        return vsprintf(
            "%s:host=%s;dbname=%s;port=%s;user=%s;password=%s;options='--client_encoding=%s'",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('database'),
                $this->get('port'),
                $this->get('user'),
                $this->get('password'),
                $this->get('charset')
            ]
        );
    }

    private function handleOci(): string
    {
        return (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ?
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
    }

    private function handleSybase(): string
    {
        return vsprintf(
            "%s:host=%s:%s/%s;charset=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('charset')
            ]
        );
    }

    private function handleSqlsrv(): string
    {
        return vsprintf(
            "%s:server=%s,%s;database=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database')
            ]
        );
    }

    private function handleMssql(): string
    {
        $this->set('driver', (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'mssql' : 'dblib');

        return (mb_strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ?
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
    }

    /**
     * @throws Exceptions
     */
    private function handleFirebird(): string
    {
        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }
        return vsprintf(
            "%s:dbname=%s/%s:%s;charset=%s",
            [
                $this->get('driver'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('charset')
            ]
        );
    }

    /**
     * @throws Exceptions
     */
    private function handleSQLite(): string
    {
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
        return $result;
    }
}
