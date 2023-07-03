<?php

use
    GenericDatabase\Engine\MySQLiEngine,

    GenericDatabase\Engine\MySQli\MySQL;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$mysql = new MySQLiEngine();
$mysql->setHost($_ENV['MYSQL_HOST'])
    ->setPort(+$_ENV['MYSQL_PORT'])
    ->setDatabase($_ENV['MYSQL_DATABASE'])
    ->setUser($_ENV['MYSQL_USER'])
    ->setPassword($_ENV['MYSQL_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        MySQL::ATTR_PERSISTENT => true,
        MySQL::ATTR_AUTOCOMMIT => true,
        MySQL::ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        MySQL::ATTR_SET_CHARSET_NAME => "utf8",
        MySQL::ATTR_OPT_INT_AND_FLOAT_NATIVE => true,
        MySQL::ATTR_OPT_CONNECT_TIMEOUT => 28800,
        MySQL::ATTR_OPT_READ_TIMEOUT => 30,
        MySQL::ATTR_READ_DEFAULT_GROUP => "MAX_ALLOWED_PACKET=50M"
    ])
    ->setException(true)
    ->connect();

var_dump($mysql);
