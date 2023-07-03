<?php

use GenericDatabase\Engine\OCIEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$oci = OCIEngine::new(PATH_ROOT . '/assets/INI/oci.ini')->connect();

var_dump($oci);
