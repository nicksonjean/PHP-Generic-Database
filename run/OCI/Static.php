<?php

use
  GenericDatabase\Engine\OCIEngine,
  GenericDatabase\Engine\OCI\OCI;

require_once __DIR__ . '/../../vendor/autoload.php';

$oci = OCIEngine::new('localhost', 1521, 'xe', 'hr', 'masterkey', 'utf8', [
  OCI::ATTR_PERSISTENT => true,
  OCI::ATTR_CONNECT_TIMEOUT => 28800,
], true)->connect();

// $oci->loadFromFile('../../tests/test.sql');

var_dump($oci);
