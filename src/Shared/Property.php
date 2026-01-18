<?php

namespace GenericDatabase\Shared;

/**
 * This trait provides a private array property intended for use with
 * magic setter and getter methods.
 *
 * Fields:
 * - `$property`: The name of the property to access.
 */
trait Property
{
    /**
     * Array property for use in magic setter and getter
     * @var array $property
     */
    private array $property = [];
}

