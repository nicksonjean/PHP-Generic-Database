<?php

namespace GenericDatabase\Interfaces\Connection;

use ReflectionException;

interface IOptions
{
    /**
     * This method is responsible for obtaining all options already defined by the user.
     *
     * @param ?int $type = null
     * @return mixed
     */
    public static function getOptions(?int $type = null): mixed;

    /**
     * This method is responsible for set options before connect in database
     *
     * @param ?array $options = null
     * @return void
     * @throws ReflectionException
     */
    public static function setOptions(?array $options = null): void;

    /**
     * This method is responsible for set options after connect in database,
     * more information's in https://www.php.net/manual/en/ibase.configuration.php
     *
     * @return void
     */
    public static function define(): void;
}
