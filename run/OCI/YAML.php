<?php

use GenericDatabase\Engine\OCIEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$oci = OCIEngine::new('../../assets/YAML/oci8.yaml')->connect();

var_dump($oci);
