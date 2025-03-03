<?php

namespace GenericDatabase\Engine\PgSQL\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    protected static IConnection $instance;

    protected static IOptions $optionsHandler;

    public function __construct(IConnection $instance, IOptions $optionsHandler)
    {
        self::$instance = $instance;
        self::$optionsHandler = $optionsHandler;
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

    private function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
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
                    $this->getOptionsHandler()->getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                        ? ' connect_timeout=' . $this->getOptionsHandler()->getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                        : '',
                    $this->get('charset')
                ]
            )
        );
        return $this->get('dsn');
    }
}
