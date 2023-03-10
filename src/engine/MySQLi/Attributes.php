<?php

namespace GenericDatabase\Engine\MySQLi;

use
  GenericDatabase\Engine\MySQLiEngine,
  GenericDatabase\Engine\MySQLi\Options;

class Attributes
{

  private static $fetchMode = \MYSQLI_BOTH;

  private static $errorMode = \MYSQLI_REPORT_ERROR;

  private static $variables = [];

  private static $charsets = [];

  private static $collations = [];

  private static $settings = [];

  /**
   * static attributes constants
   * 
   */
  public static $attributeList = [
    'AUTOCOMMIT',
    'ERRMODE',
    'CASE',
    'CLIENT_VERSION',
    'CONNECTION_STATUS',
    'PERSISTENT',
    'SERVER_INFO',
    'SERVER_VERSION',
    'TIMEOUT',
    'EMULATE_PREPARES',
    'DEFAULT_FETCH_MODE',
    'CHARACTER_SET',
    'COLLATION'
  ];

  public static function init()
  {
    self::setFetchMode();
    self::setErrorMode();
    self::setVariables();
    self::setCharacterSet(CharsetType::Connection);
    self::setCollation(CharsetType::Connection);
    self::setSettings();
  }

  /**
   * @desc Optionally set the return mode.
   *
   * @param <int> $type The mode: 1 for MYSQLI_NUM, 2 for MYSQLI_ASSOC, default is MYSQLI_BOTH
   */
  private static function setFetchMode($type = null)
  {
    switch ($type) {
      case 1:
        self::$fetchMode = MYSQLI_NUM;
        break;
      case 2:
        self::$fetchMode = MYSQLI_ASSOC;
        break;
      default:
        self::$fetchMode = MYSQLI_BOTH;
        break;
    }
  }

  private static function setErrorMode()
  {
    self::$errorMode = (MySQLiEngine::getInstance()->getException()) ? \MYSQLI_REPORT_ERROR : \MYSQLI_REPORT_OFF;
    if (MySQLiEngine::getInstance()->getException()) {
      $driver = new \MySQLi_Driver();
      $driver->report_mode = (int) self::$errorMode;
    }
  }

  private static function setVariables()
  {
    if (!($res = MySQLiEngine::getInstance()->getConnection()->query("SHOW VARIABLES LIKE '%character%'"))) {
      printf("[%d] %s\n", MySQLiEngine::getInstance()->getConnection()->errno, MySQLiEngine::getInstance()->getConnection()->error);
      return self::$variables;
    }

    while ($row = $res->fetch_assoc()) {
      self::$variables[$row['Variable_name']] = $row['Value'];
    }

    $res->free_result();
  }

  private static function getVariables(?CharsetType $type = CharsetType::Connection)
  {
    return !is_null($type) ? self::$variables[$type->getInverseCharsetType()] : self::$variables;
  }

  private static function setCharacterSet(?CharsetType $type = CharsetType::Connection)
  {
    if (
      !($res = MySQLiEngine::getInstance()->getConnection()->query(sprintf("SHOW CHARACTER SET LIKE '%s'", self::$variables[$type->getCharsetType()]))) ||
      !(self::$charsets = $res->fetch_assoc())
    ) {
      printf("[%d] %s\n", MySQLiEngine::getInstance()->getConnection()->errno, MySQLiEngine::getInstance()->getConnection()->error);
      return self::$charsets;
    }

    self::$variables[$type->getInverseCharsetType()] = [
      'charset' => self::$charsets['Charset'],
      'description' => self::$charsets['Description'],
      'collation' => self::$charsets['Default collation'],
      'maxlen' => self::$charsets['Maxlen'],
      'sortlen' => NULL,
      'default' => NULL,
      'compiled' => NULL,
      'id' => NULL
    ];

    $res->free_result();
  }

  private static function getCharacterSet()
  {
    return self::$charsets;
  }

  private static function setCollation(?CharsetType $type = CharsetType::Connection)
  {
    if (
      !($res = MySQLiEngine::getInstance()->getConnection()->query(sprintf("SHOW COLLATION LIKE '%s'", self::$charsets['Default collation']))) ||
      !(self::$collations = $res->fetch_assoc())
    ) {
      printf("[%d] %s\n", MySQLiEngine::getInstance()->getConnection()->errno, MySQLiEngine::getInstance()->getConnection()->error);
      return self::$collations;
    }

    self::$variables[$type->getInverseCharsetType()]['sortlen'] = self::$collations['Sortlen'];
    self::$variables[$type->getInverseCharsetType()]['default'] = self::$collations['Default'];
    self::$variables[$type->getInverseCharsetType()]['compiled'] = self::$collations['Compiled'];
    self::$variables[$type->getInverseCharsetType()]['id'] = self::$collations['Id'];

    $res->free_result();
  }

  private static function getCollation()
  {
    return self::$collations;
  }

  private static function setSettings()
  {
    if (!($res = MySQLiEngine::getInstance()->getConnection()->query("SHOW SESSION VARIABLES WHERE Variable_name IN('autocommit', 'lower_case_table_names', 'sql_mode', 'connect_timeout', 'interactive_timeout', 'wait_timeout', 'net_read_timeout', 'net_write_timeout');"))) {
      printf("[%d] %s\n", MySQLiEngine::getInstance()->getConnection()->errno, MySQLiEngine::getInstance()->getConnection()->error);
      return self::$settings;
    }

    while ($row = $res->fetch_assoc()) {
      self::$settings[$row['Variable_name']] = $row['Value'];
    }

    $res->free_result();
  }

  private static function getSettings()
  {
    return self::$settings;
  }

  /**
   * Define all MySQLi attibute of the conection a ready exist
   * 
   * @return void
   */
  public static function define(?CharsetType $type = CharsetType::Connection): void
  {
    self::init();
    $result = [];
    foreach (self::$attributeList as $key => $value) {
      $result[self::$attributeList[$key]] = match (self::$attributeList[$key]) {
        'AUTOCOMMIT' =>  self::$settings['autocommit'] === 'ON' ? '1' : '0',
        'ERRMODE' => (string) self::$errorMode,
        'CASE' => self::$settings['lower_case_table_names'] === '1' ? '0' : '1',
        'CLIENT_VERSION' => MySQLiEngine::getInstance()->getConnection()->client_info,
        'CONNECTION_STATUS' => MySQLiEngine::getInstance()->getConnection()->host_info,
        'PERSISTENT' => (int) !isset(Options::getOptions()['assoc']['ATTR_PERSISTENT']) ? 0 : (int) Options::getOptions()['assoc']['ATTR_PERSISTENT'],
        'SERVER_INFO' => MySQLiEngine::getInstance()->getConnection()->stat(),
        'SERVER_VERSION' => MySQLiEngine::getInstance()->getConnection()->server_info,
        'TIMEOUT' => self::$settings['connect_timeout'],
        'EMULATE_PREPARES' => 'FAKE',
        'DEFAULT_FETCH_MODE' => (string) self::$fetchMode,
        'CHARACTER_SET' => self::getVariables($type)['charset'],
        'COLLATION' => self::getVariables($type)['collation']
      };
    };
    MySQLiEngine::getInstance()?->setAttributes((array) $result);
  }
}
