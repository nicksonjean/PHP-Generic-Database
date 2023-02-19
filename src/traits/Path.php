<?php
trait Path
{
  public static function toAbsolute($path)
  {
    if (!file_exists($path)) {
      $path = substr($path, 6);
    } else if (!file_exists($path)) {
      $message = sprintf('File not founded in %s', $path);
      throw new Exception($message);
    }
    return realpath($path);
  }

  public static function isAbsolute($path)
  {
    if (!is_string($path)) {
      $message = sprintf('String expected but was given %s', gettype($path));
      throw new Exception($message);
    }
    if (!ctype_print($path)) {
      $message = 'Path can NOT have non-printable characters or be empty';
      throw new Exception($message);
    }
    $regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';
    $regExp .= '(?<root>(?:[[:alpha:]]:/|/)?)';
    $regExp .= '(?<path>(?:[[:print:]]*))$%';
    $parts = [];
    if (!preg_match($regExp, $path, $parts)) {
      $message = sprintf('Path is NOT valid, was given %s', $path);
      throw new Exception($message);
    }
    if ('' !== $parts['root']) {
      return true;
    }
    return false;
  }
}
