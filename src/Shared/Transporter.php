<?php

namespace GenericDatabase\Shared;

use GenericDatabase\Shared\Property;

trait Transporter
{
    use Property;

    /**
     * Sleep instance uesd by serialize/unserialize
     *
     * @return array
     */
    public function __sleep(): array
    {
        $vars = get_class_vars(get_class($this));
        foreach ($vars as $field => $value) {
            if ($field === 'property' && $field !== '' && $value !== '') {
                $this->property[$field] = $value;
            }
        }
        return array_keys(get_object_vars($this));
    }

    /**
     * Wakeup instance uesd by serialize/unserialize
     *
     * @return void
     */
    public function __wakeup()
    {
        self::$instance = null;
        if (isset($this->property) && is_array($this->property)) {
            foreach ($this->property as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }
}
