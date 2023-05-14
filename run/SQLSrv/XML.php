<?php

use GenericDatabase\Engine\SQLSrvEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new('../../assets/XML/sqlsrvn.xml')->connect();

var_dump($sqlsrv);
