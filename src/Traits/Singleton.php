<?php

namespace GenericDatabase\Traits;

trait Singleton
{
  /**
   * Static class instance
   */
  private static $instance;

  /**
   * Create or obtain a singleton instance
   * 
   * @return self
   */
  public static function getInstance(): self
  {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Create or obtain a singleton instance
   * 
   * @param $instance The instance object to be defined
   * @return self
   */
  public static function setInstance($instance): self
  {
    self::$instance = $instance;
    return self::$instance;
  }

  /**
   * Clear a singleton instance
   * 
   * @return void
   */
  protected function clearInstance(): void
  {
    self::$instance = null;
  }
}
