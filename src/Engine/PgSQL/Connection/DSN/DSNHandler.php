<?php

namespace GenericDatabase\Engine\PgSQL\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\DSN\IDSN;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Engine\PgSQL\Connection\Options;


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
        if (!extension_loaded('pgsql')) {
            throw new Exceptions("Invalid or not loaded 'pgsql' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "host=%s port=%s dbname=%s user=%s password=%s%s options='--client_encoding=%s'",
                [
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('user'),
                    $this->get('password'),
                    Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                        ? ' connect_timeout=' . Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                        : '',
                    $this->get('charset')
                ]
            )
        );
        return $this->get('dsn');
    }
}
