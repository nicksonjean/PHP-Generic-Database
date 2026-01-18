<?php

namespace GenericDatabase\Engine\SQLite\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\Connection\IDSN;

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

    private function handleSQLite(bool $isMemory = false): string
    {

        return vsprintf(
            ($isMemory) ? "sqlite::%s:" : "sqlite:%s",
            [
                $this->get('database')
            ]
        );
    }

    private function isFilable(): bool
    {
        return !Path::isAbsolute($this->get('database')) && $this->get('database') !== 'memory';
    }

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('sqlite3')) {
            throw new Exceptions("Invalid or not loaded 'sqlite3' extension in PHP.ini settings");
        }

        if ($this->isFilable()) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $this->set(
            'dsn',
            match ($this->get('database')) {
                'memory' => $this->handleSQLite(true),
                default => $this->handleSQLite(false),
            }
        );

        return $this->get('dsn');
    }
}
