<?php

namespace GenericDatabase\Shared;

trait Transporter
{
    use Property;
    use Singleton;

    /**
     * Sleep instance uesd by serialize/unserialize
     *
     * @return array
     */
    public function __sleep(): array
    {
        $vars = get_class_vars(get_class($this));
        foreach ($vars as $field => $value) {
            if (!empty($value)) {
                $this->property[$field] = $value;
            }
        }
        return array_keys(get_object_vars($this));
    }

    /**
     * __wakeup instance uesd by serialize/unserialize
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->getInstance();
    }
}