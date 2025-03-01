<?php

namespace GenericDatabase\Engine\Firebird\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;
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

    public function parse(): string|Exceptions
    {
        if (!extension_loaded('interbase')) {
            throw new Exceptions("Invalid or not loaded 'interbase' extension in PHP.ini settings");
        }

        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $this->set(
            'dsn',
            vsprintf(
                "firebird://%s:%s@%s:%s//%s?charset=%s",
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
