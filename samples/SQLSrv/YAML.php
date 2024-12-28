<?php

use GenericDatabase\Engine\SQLSrvConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$sqlsrv = SQLSrvConnection::new(PATH_ROOT . '/resources/dsn/yaml/sqlsrv.yaml')->connect();

var_dump($sqlsrv);
