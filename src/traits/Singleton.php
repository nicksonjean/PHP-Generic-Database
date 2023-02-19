<?php
trait Singleton
{
  /**
   * static class instance
   */
  private static $instance;

  /**
   * create or obtain a singleton instance
   */
  public static function getInstance(): self
  {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function setInstance($instance): self
  {
    self::$instance = $instance;
    return self::$instance;
  }

  protected function clearInstance(): void
  {
    self::$instance = null;
  }
}
