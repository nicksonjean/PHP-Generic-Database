<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Property;

trait Setter
{
    use Property;

    /**
     * This method is run when writing data to inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @param mixed $value The value to be defined
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->property[$name] = $value;
        // return $this;
    }
}
