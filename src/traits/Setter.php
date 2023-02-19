<?php

require_once 'Property.php';

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
