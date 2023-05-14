<?php

use GenericDatabase\Engine\SQLSrvEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new('../../assets/JSON/sqlsrvn.json')->connect();

var_dump($sqlsrv);
