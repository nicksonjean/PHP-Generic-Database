<?php

namespace GenericDatabase\Shared;

trait Transporter
{
    use Property;

    private static mixed $instance = null;

    /**
     * Sleep instance used by serialize/unserialize
     *
     * @return array
     */
    public function __sleep(): array
    {
        $vars = get_class_vars($this::class);
        foreach ($vars as $field => $value) {
            if ($field === 'property' && $value !== '') {
                $this->property[$field] = $value;
            }
        }
        return array_keys(get_object_vars($this));
    }

    /**
     * Wakeup instance used by serialize/unserialize
     *
     * @return void
     */
    public function __wakeup()
    {
        if (isset($this->property)) {
            foreach ($this->property as $field => $value) {
                $this->{$field} = $value;
            }
        }
    }
}
