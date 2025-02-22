<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\DSN\IDSN;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Engine\SQLSrv\Connection\Options;

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

    /**
     * @throws Exceptions
     */
    public function parse(): string|Exceptions
    {
        if (!extension_loaded('sqlsrv')) {
            throw new Exceptions("Invalid or not loaded 'sqlsrv' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "sqlsrv://%s:%s@%s:%s/?database=%s&charset=%s%s",
                [
                    $this->get('user'),
                    $this->get('password'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('charset'),
                    Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                        ? '&timeout=' . Options::getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                        : '',
                ]
            )
        );
        return $this->get('dsn');
    }
}
