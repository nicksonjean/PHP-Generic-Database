<?php

namespace GenericDatabase\Abstract;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;

#[AllowDynamicProperties]
class AbstractOptions
{
    protected static array $options = [];

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
    /**
     * This method is responsible for obtain all options already defined by user
     *
     * @param mixed $type = null
     * @return mixed
     */
    public function getOptions(?int $type = null): mixed
    {
        return !is_null($type) ? self::$options[$type] ?? null : self::$options;
    }
}
