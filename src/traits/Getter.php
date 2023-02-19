<?php

require_once 'Property.php';

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
