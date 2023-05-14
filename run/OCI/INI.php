<?php

use GenericDatabase\Engine\OCIEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$oci = OCIEngine::new('../../assets/INI/oci8.ini')->connect();

var_dump($oci);
