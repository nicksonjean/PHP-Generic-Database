<?php

require_once 'Property.php';
require_once 'Singleton.php';

trait Transporter
{
  use Property, Singleton;
  /**
   * sleep instance uesd by serialize/unserialize
   *
   * @return  void
   */
  public function __sleep()
  {
    $vars = get_class_vars(get_class($this));
    foreach ($vars as $field => $value) {
      if (!empty($value))
        $this->property[$field] = $value;
    }
    return array_keys(get_object_vars($this));
  }

  /**
   * __wakeup instance uesd by serialize/unserialize
   *
   * @return  void
   */
  public function __wakeup()
  {
    return $this->getInstance();
  }
}
