<?php

use GenericDatabase\Engine\OCIEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$oci = OCIEngine::new(PATH_ROOT . '/resources/dsn/yaml/oci.yaml')->connect();

var_dump($oci);
