<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Property;

trait Getter
{
  use Property;
  /**
   * define mutator aka setter
   * @param mixed $field
   * 
   * @return mixed
   */
  public function __get($field)
  {
    return array_key_exists($field, $this->property) ? $this->property[$field] : null;
  }
}
