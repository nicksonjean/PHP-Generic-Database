<?php

namespace GenericDatabase\Traits;

use GenericDatabase\Traits\Property;

trait Getter
{
  use Property;

  /**
   * This method is utilized for reading data from inaccessible (protected or private) or non-existing properties.
   * 
   * @param string $name Argument to be tested
   * @return mixed
   */
  public function __get(string $name): mixed
  {
    return array_key_exists($name, $this->property) ? $this->property[$name] : null;
  }
}
