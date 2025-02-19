<?php

namespace GenericDatabase\Interfaces\Connection;

interface IAttributes
{
    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public static function define(): void;
}
