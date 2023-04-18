<?php

use
  GenericDatabase\Engine\FBirdEngine,
  GenericDatabase\Engine\FBird\FBird;

require_once __DIR__ . '/../../vendor/autoload.php';

$fbird = FBirdEngine::new('localhost', 3050, '../../assets/DB.FDB', 'sysdba', 'masterkey', 'utf8', [
  FBird::ATTR_PERSISTENT => true,
  FBird::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

// $pgsql->loadFromFile('../../tests/test.sql');

var_dump($fbird);
