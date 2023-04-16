<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Property;

trait Cleaner
{
  use Property;

  /**
   * Isset property
   * 
   * @param mixed $field
   * @return boolean
   */
  public function __isset($field)
  {
    return isset($this->property[$field]);
  }

  /**
   * Unset property
   * 
   * @param mixed $field
   * @return  void
   */
  public function __unset($field)
  {
    unset($this->property[$field]);
  }
}
