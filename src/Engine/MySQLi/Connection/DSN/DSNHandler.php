<?php

namespace GenericDatabase\Engine\MySQLi\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
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

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('mysqli')) {
            throw new Exceptions("Invalid or not loaded 'mysqli' extension in PHP.ini settings");
        }

        $sanitize = fn(bool $default = false) => vsprintf(
            "mysql://%s:%s@%s:%s/%s?charset=%s",
            [
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
            $sanitize(true)
        );
        return $sanitize(false);
    }
}

