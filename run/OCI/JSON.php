<?php

use GenericDatabase\Engine\OCIEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$oci = OCIEngine::new('../../assets/JSON/oci8.json')->connect();

var_dump($oci);
