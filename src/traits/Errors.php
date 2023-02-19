<?php
trait Errors
{
  static private $error;

  private static function getError()
  {
    self::$error = ini_get('display_errors');
    return self::$error;
  }

  private static function setError($value)
  {
    self::$error = ini_set('display_errors', $value);
    return self::$error;
  }

  public static function turnOff()
  {
    return self::setError(0);
  }

  public static function turnOn()
  {
    return self::setError(1);
  }

  public static function throw($ex)
  {
    die(json_encode(array(
      'message' => $ex->getMessage(),
      'location' => "{$ex->getFile()}:{$ex->getLine()}"
    )));
  }

  public static function newThrow($ex, $message)
  {
    die(json_encode(array(
      'message' => $message,
      'location' => "{$ex->getFile()}:{$ex->getLine()}"
    )));
  }
}
