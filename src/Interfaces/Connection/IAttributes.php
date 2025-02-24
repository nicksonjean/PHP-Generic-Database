<?php

namespace GenericDatabase\Interfaces\Connection;

interface IAttributes
{
    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @return void
     */
    public function define(): void;
}
