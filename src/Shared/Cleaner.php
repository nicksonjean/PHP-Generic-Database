<?php

namespace GenericDatabase\Shared;

trait Cleaner
{
    use Property;

    /**
     * This method is triggered by calling isset() or empty() on
     * inaccessible (protected or private) or non-existing properties.
     *
     * @param string $name Argument to be tested
     * @return bool
     */
    public function __isset(string $name): bool
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
    public function __unset(string $name): void
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
