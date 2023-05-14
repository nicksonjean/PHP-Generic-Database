<?php

use GenericDatabase\Engine\SQLSrvEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new('../../assets/INI/sqlsrvn.ini')->connect();

var_dump($sqlsrv);
