<?php

use
  GenericDatabase\Engine\SQLSrvEngine,
  GenericDatabase\Engine\SQLSrv\SQLSrv;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlsrv = SQLSrvEngine
  ::setHost('localhost')
  ::setPort(1433)
  ::setDatabase('demodev')
  ::setUser('sa')
  ::setPassword('masterkey')
  ::setCharset('utf8')
  ::setOptions([
    SQLSrv::ATTR_PERSISTENT => true,
    SQLSrv::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ::setException(true)
  ->connect();

var_dump($sqlsrv);
