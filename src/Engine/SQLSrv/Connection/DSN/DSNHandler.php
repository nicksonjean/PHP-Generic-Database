<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;

#[AllowDynamicProperties]
class DSNHandler implements IDSN
{
    private IConnection $connection;

    private static IOptions $optionsHandler;

    public function __construct(IConnection $connection, IOptions $optionsHandler)
    {
        $this->connection = $connection;
        self::$optionsHandler = $optionsHandler;
    }

    public function getInstance(): IConnection
    {
        return $this->connection;
    }

    private function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    private function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    private function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    /**
     * @throws Exceptions
     */
    public function parse(): string|Exceptions
    {
        if (!extension_loaded('sqlsrv')) {
            throw new Exceptions("Invalid or not loaded 'sqlsrv' extension in PHP.ini settings");
        }

        $this->set(
            'dsn',
            vsprintf(
                "sqlsrv://%s:%s@%s:%s/?database=%s&charset=%s%s",
                [
                    $this->get('user'),
                    $this->get('password'),
                    $this->get('host'),
                    $this->get('port'),
                    $this->get('database'),
                    $this->get('charset'),
                    $this->getOptionsHandler()->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                        ? '&timeout=' . $this->getOptionsHandler()->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                        : '',
                ]
            )
        );
        return $this->get('dsn');
    }
}
