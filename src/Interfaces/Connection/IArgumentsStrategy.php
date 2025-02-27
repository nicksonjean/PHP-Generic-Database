<?php

namespace GenericDatabase\Interfaces\Connection;

interface IArgumentsStrategy
{
    public function setConstant(array $value): array;
}
