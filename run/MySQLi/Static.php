<?php

use
  GenericDatabase\Engine\MySQLiEngine,
  GenericDatabase\Engine\MySQli\MySQL;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = MySQLiEngine::new('localhost', 3306, 'demodev', 'root', '', 'utf8', [
  MySQL::ATTR_PERSISTENT => true,
  MySQL::ATTR_INIT_COMMAND => "SET AUTOCOMMIT=1",
  MySQL::ATTR_SET_CHARSET_NAME => "utf8",
  MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
  MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
  MySQL::ATTR_OPT_READ_TIMEOUT => 30,
  MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
], true)->connect();

// $mysql->loadFromFile('../../tests/test.sql');

var_dump($mysql);
