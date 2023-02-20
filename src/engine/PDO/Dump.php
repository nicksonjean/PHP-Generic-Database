<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Engine\PDOEngine;

class Dump
{
  /**
   * This is a regex array to uncomment strings in a codebase
   */
  private static $regex = [
    'uncomment' => '@(--[^\r\n]*)|(\#[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/)\s*;@ms',
    'unicode' => '/\R+/'
  ];

  /**
   * Settings for memory limit and time limit
   */
  private static $settings = [
    'memoryLimit' => '5120M',
    'timeLimit' => 0
  ];

  /**
   * Import SQL dump from file - extremely fast.
   * @param  array<callable(int, ?float): void>  $onProgress
   * @return int  count of commands
   */
  public static function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int
  {
    ini_set('memory_limit', (string) self::$settings['memoryLimit']);
    set_time_limit((int) self::$settings['memoryLimit']);

    $handle = @fopen($file, 'r');
    if (!$handle) {
      throw new \Exception("Cannot open file '$file'.");
    }

    $stat = fstat($handle);
    $count = $size = 0;
    $sql = '';

    while (($string = fgets($handle)) !== false) {
      $size += strlen($string);

      $uncomment = function ($string = '') {
        return preg_replace((string) self::$regex['unicode'], ' ', (($string == '') ?  '' : preg_replace((string) self::$regex['uncomment'], '', $string)));
      };

      if (strlen($uncomment($string)) > 1) {
        if (!strncasecmp($uncomment($string), "DELIMITER ", 10)) {
          $delimiter = trim(substr($uncomment($string), 10));
        } elseif (substr($ts = rtrim($uncomment($string)), -strlen($delimiter)) === $delimiter) {
          $sql .= substr($ts, 0, -strlen($delimiter));
          PDOEngine::getInstance()?->exec($sql);
          $sql = '';
          $count++;
          if ($onProgress) {
            $onProgress($count, isset($stat['size']) ? $size * 100 / $stat['size'] : null);
          }
        } else {
          $sql .= $uncomment($string);
        }
      }
    }

    if (rtrim($sql) !== '') {
      PDOEngine::getInstance()?->exec($sql);
      $count++;
      if ($onProgress) {
        $onProgress($count, isset($stat['size']) ? 100 : null);
      }
    }

    fclose($handle);
    return $count;
  }
}
