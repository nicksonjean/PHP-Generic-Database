<?php

namespace GenericDatabase\Generic\Connection;

use GenericDatabase\Shared\Property;
use GenericDatabase\Shared\Getter;
use GenericDatabase\Shared\Setter;
use GenericDatabase\Shared\Cleaner;

class Settings
{
    use Property, Getter, Setter, Cleaner;

    public function __construct(array $property = [])
    {
        $this->property = $property;
    }

    public function __debugInfo(): array
    {
        return $this->property;
    }
}
