<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Property;

trait Setter
{
  use Property;

  /**
   * define accessor aka getter
   * @param mixed $field
   * @param mixed $value
   *
   * @return mixed
   */
  public function __set($field, $value)
  {
    $this->property[$field] = $value;
    return $this;
  }
}
