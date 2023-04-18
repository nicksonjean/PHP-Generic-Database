<?php

use
  GenericDatabase\Engine\FBirdEngine,
  GenericDatabase\Engine\FBird\FBird;

require_once __DIR__ . '/../../vendor/autoload.php';

$fbird = new FBirdEngine();
$fbird->setHost('localhost')
  ->setPort(3050)
  ->setDatabase('../../assets/DB.FDB')
  ->setUser('sysdba')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    FBird::ATTR_PERSISTENT => true,
    FBird::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ->setException(true)
  ->connect();

var_dump($fbird);
