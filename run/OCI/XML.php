<?php

use GenericDatabase\Engine\OCIEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$oci = OCIEngine::new(PATH_ROOT . '/assets/XML/oci.xml')->connect();

var_dump($oci);
