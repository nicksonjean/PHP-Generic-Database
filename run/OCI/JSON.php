<?php

use GenericDatabase\Engine\OCIEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$oci = OCIEngine::new(PATH_ROOT . '/assets/JSON/oci.json')->connect();

var_dump($oci);
