<?php

namespace GenericDatabase\Traits;

trait Singleton
{
  /**
   * static class instance
   */
  private static $instance;

  /**
   * create or obtain a singleton instance
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
   * create or obtain a singleton instance
   * 
   * @param $instance
   * @return self
   */
  public static function setInstance($instance): self
  {
    self::$instance = $instance;
    return self::$instance;
  }

  /**
   * clear a singleton instance
   * 
   * @param $instance
   * @return void
   */
  protected function clearInstance(): void
  {
    self::$instance = null;
  }
}
