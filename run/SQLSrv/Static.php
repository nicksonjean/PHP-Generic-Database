<?php

use
  GenericDatabase\Engine\SQLSrvEngine,
  GenericDatabase\Engine\SQLSrv\SQLSrv;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new('localhost', 1433, 'demodev', 'sa', 'masterkey', 'utf8', [
  SQLSrv::ATTR_PERSISTENT => true,
  SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

// $pgsql->loadFromFile('../../tests/test.sql');

var_dump($sqlsrv);
