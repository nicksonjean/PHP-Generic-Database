<?php

namespace GenericDatabase\Interfaces\Connection;

interface IArgumentsAbstract
{
    public function setType(mixed $value): string|int|bool;

    public function callArgumentsByFormat(string $format, mixed $arguments): IConnection;

    public function callWithByStaticArray(array $arguments): IConnection;

    public function callWithByStaticArgs(array $arguments): IConnection;

    public function call(string $name, array $arguments): IConnection;
}
