<?php

namespace GenericDatabase\Traits;

trait Property
{
  /**
   * Array property for use in magic setter and getter
   */
  public $property = [];

  /**
   * Debug Info property
   * 
   * @return  array
   */
  public function __debugInfo()
  {
    return ['property' => [
      'engine' => $this->getEngine() ?? 'native', // Only Strategy
      'driver' => $this->getDriver() ?? 'native', // Only PDO
      'host' => $this->getHost(),
      'port' => $this->getPort(),
      'database' => $this->getDatabase(),
      'user' => $this->getUser(),
      'password' => $this->getPassword(),
      'charset' => $this->getCharset(),
      'options' => $this->getOptions(),
      'exception' => $this->getException(),
      'dsn' => $this->getDsn(),
      'attributes' => $this->getAttributes(),
      'connected' => $this->getConnected()
    ]];
  }
}
