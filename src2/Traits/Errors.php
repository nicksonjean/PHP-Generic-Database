<?php

namespace GenericDatabase\Traits;

trait Errors
{
  /**
   * Store a Error
   */
  static private $error;

  /**
   * Get error from display_errors directive
   * @return string|false
   */
  private static function getError()
  {
    self::$error = ini_get('display_errors');
    return self::$error;
  }

  /**
   * Set error to display_errors directive
   * @return string|false
   */
  private static function setError($value)
  {
    self::$error = ini_set('display_errors', $value);
    return self::$error;
  }

  /**
   * Turn off errors
   * @return string|false
   */
  public static function turnOff()
  {
    return self::setError(0);
  }

  /**
   * Turn on errors
   * @return string|false
   */
  public static function turnOn()
  {
    return self::setError(1);
  }

  /**
   * Throw a exception
   * 
   * @param object $ex 
   * @return string|false
   */
  public static function throw($ex): never
  {
    die(json_encode(array(
      'message' => $ex->getMessage(),
      'location' => "{$ex->getFile()}:{$ex->getLine()}"
    )));
  }

  /**
   * Launch a new Throw by a exception
   * 
   * @param object $ex 
   * @param object $message
   * @return string|false
   */
  public static function newThrow($ex, $message): never
  {
    die(json_encode(array(
      'message' => $message,
      'location' => "{$ex->getFile()}:{$ex->getLine()}"
    )));
  }
}
