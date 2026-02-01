<?php

namespace GenericDatabase\Engine\YAML\Connection\DSN;

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

    private function handleYAML(bool $isMemory = false): string
    {

        return vsprintf(
            ($isMemory) ? "yaml::%s:" : "yaml:%s",
            [
                $this->get('database')
            ]
        );
    }

    public function parse(): string|Exceptions
    {
        $this->set(
            'dsn',
            match ($this->get('database')) {
                'memory' => $this->handleYAML(true),
                default => $this->handleYAML(false),
            }
        );

        return $this->get('dsn');
    }
}
