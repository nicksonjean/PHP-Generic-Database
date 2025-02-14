<?php

namespace GenericDatabase\Shared;

trait Objectable
{
    public function __get($name)
    {
        return $this->$name ?? ($this->$name = new self());
    }

    public function __set($name, $value)
    {
        if (is_array($value) && isset($this->$name) && $this->$name instanceof self) {
            foreach ($value as $k => $v) {
                $this->$name->$k = $v;
            }
        } else {
            $this->$name = $value;
        }
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            $result[$key] = ($value instanceof self) ? $value->toArray() : $value;
        }
        return $result;
    }
}
