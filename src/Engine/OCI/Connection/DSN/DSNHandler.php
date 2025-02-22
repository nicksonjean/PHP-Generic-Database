<?php

namespace GenericDatabase\Engine\OCI\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\DSN\IDSN;
use GenericDatabase\Interfaces\IConnection;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
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

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('oci8')) {
            throw new Exceptions("Invalid or not loaded 'oci8' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "oci8://%s:%s@%s:%s/?service=%s&charset=%s",
                [
                    $this->get('user'),
                    $this->get('password'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('charset')
                ]
            )
        );
        return $this->get('dsn');
    }
}
