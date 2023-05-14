<?php

use GenericDatabase\Engine\SQLSrvEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new('../../assets/YAML/sqlsrvn.yaml')->connect();

var_dump($sqlsrv);
