<?php

use GenericDatabase\Engine\SQLSrvEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new(PATH_ROOT . '/assets/YAML/sqlsrv.yaml')->connect();

var_dump($sqlsrv);
