<?php

namespace GenericDatabase\Engine\SQLite\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Path;
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
        if (!extension_loaded('sqlite3')) {
            throw new Exceptions("Invalid or not loaded 'sqlite3' extension in PHP.ini settings");
        }

        if (!Path::isAbsolute($this->get('database')) && $this->get('database') !== 'memory') {
            $this->set('database', Path::toAbsolute($this->get('database')));
            $this->set(
                'dsn',
                vsprintf(
                    "sqlite:%s",
                    [
                        $this->get('database')
                    ]
                )
            );
        } else {
            $this->set(
                'dsn',
                vsprintf(
                    "sqlite::%s:",
                    [
                        $this->get('database')
                    ]
                )
            );
        }
        return $this->get('dsn');
    }
}
