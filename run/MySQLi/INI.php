<?php

use GenericDatabase\Engine\MySQLiEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$mysql = MySQLiEngine::new('../../assets/INI/mysqli.ini')->connect();

var_dump($mysql);
