<?php

namespace GenericDatabase\Interfaces\Connection;

interface IArguments
{
    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public static function call(string $name, array $arguments): static;
}
