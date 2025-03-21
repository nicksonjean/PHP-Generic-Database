<?php

use GenericDatabase\Engine\OCIConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$oci = OCIConnection::new(PATH_ROOT . '/resources/dsn/json/oci.json')->connect();

var_dump($oci);
