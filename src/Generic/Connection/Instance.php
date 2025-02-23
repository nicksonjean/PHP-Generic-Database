<?php

namespace GenericDatabase\Generic\Connection;

use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;

trait Instance
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
}
