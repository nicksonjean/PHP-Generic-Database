<?php

use GenericDatabase\Engine\SQLSrvEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlsrv = SQLSrvEngine::new(PATH_ROOT . '/resources/dsn/ini/sqlsrv.ini')->connect();

var_dump($sqlsrv);
