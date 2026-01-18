<?php

namespace GenericDatabase\Engine\Firebird\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Path;
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
        if (!extension_loaded('interbase')) {
            throw new Exceptions("Invalid or not loaded 'interbase' extension in PHP.ini settings");
        }

        if (!Path::isAbsolute($this->get('database'))) {
            $this->set('database', Path::toAbsolute($this->get('database')));
        }

        $sanitize = fn(bool $default = false) => vsprintf(
            "%s://%s:%s@%s:%s//%s?charset=%s",
            [
                in_array(strtolower($this->get('driver') ?? ''), ['ibase', 'interbase']) ? 'interbase' : 'firebird',
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
        return vsprintf('%s/%s:%s', [$this->get('host'), $this->get('port'), $this->get('database')]);
    }
}

