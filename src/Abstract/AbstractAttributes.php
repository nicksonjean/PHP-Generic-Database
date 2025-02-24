<?php

namespace GenericDatabase\Abstract;

use AllowDynamicProperties;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Shared\Run;

#[AllowDynamicProperties]
class AbstractAttributes
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

    public function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }
}
