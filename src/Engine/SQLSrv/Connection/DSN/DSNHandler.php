<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\DSN;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IDSN;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Generic\Connection\SensitiveValue;

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

        $sanitize = fn(bool $default = false) => vsprintf(
            "sqlsrv://%s:%s@%s:%s/?database=%s&charset=%s%s",
            [
                $this->get('user'),
                $default ? (new SensitiveValue($this->get('password')))->getMaskedValue() : $this->get('password'),
                $this->get('host'),
                $this->get('port'),
                $this->get('database'),
                $this->get('charset'),
                $this->getOptionsHandler()->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                    ? '&timeout=' . $this->getOptionsHandler()->getOptions(SQLSrv::ATTR_CONNECT_TIMEOUT)
                    : '',
            ]
        );

        $this->set(
            'dsn',
            $sanitize(true)
        );
        return $sanitize(false);
    }
}

