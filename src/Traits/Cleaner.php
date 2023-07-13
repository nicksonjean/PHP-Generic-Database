<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Property;

trait Cleaner
{
    use Property;

    /**
     * This method is triggered by calling isset() or empty() on
     * inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return void
     */
    public function __isset(string $name)
    {
        return isset($this->property[$name]);
    }

    /**
     * This method is invoked when unset() is used on inaccessible
     * (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return void
     */
    public function __unset(string $name)
    {
        unset($this->property[$name]);
    }

    /**
     * Triggered when invoking an echo command
     *
     * @return string
     */
    public function __toString(): string
    {
        return '';
    }
}
