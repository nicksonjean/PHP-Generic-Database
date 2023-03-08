<?php

use
  GenericDatabase\Engine\MySQLiEngine,
  GenericDatabase\Engine\MySQli\MySQL;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = new MySQLiEngine();
$mysql->setHost('localhost')
  ->setPort(3306)
  ->setDatabase('demodev')
  ->setUser('root')
  ->setPassword('')
  ->setCharset('utf8')
  ->setOptions([
    MySQL::ATTR_PERSISTENT->value(true),
    MySQL::ATTR_INIT_COMMAND->value("SET AUTOCOMMIT=1"),
    MySQL::ATTR_SET_CHARSET_NAME->value("utf8"),
    MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE->value(true),
    MySQL::ATTR_OPT_CONNECT_TIMEOUT->value(28800),
    MySQL::ATTR_OPT_READ_TIMEOUT->value(30),
    MySQL::ATTR_READ_DEFAULT_GROUP->value("MAX_ALLOWED_PACKET=50M")
  ])
  ->setException(true)
  ->connect();

var_dump($mysql);
