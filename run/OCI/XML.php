<?php

use GenericDatabase\Engine\OCIEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$oci = OCIEngine::new('../../assets/XML/oci.xml')->connect();

var_dump($oci);
