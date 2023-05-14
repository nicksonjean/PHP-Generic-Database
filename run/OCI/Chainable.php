<?php

use
  GenericDatabase\Engine\OCIEngine,
  GenericDatabase\Engine\OCI\OCI;

require_once __DIR__ . '/../../vendor/autoload.php';

$oci = new OCIEngine();
$oci->setHost('localhost')
  ->setPort(1521)
  ->setDatabase('xe')
  ->setUser('hr')
  ->setPassword('masterkey')
  ->setCharset('utf8')
  ->setOptions([
    OCI::ATTR_PERSISTENT => true,
    OCI::ATTR_CONNECT_TIMEOUT => 28800,
  ])
  ->setException(true)
  ->connect();

var_dump($oci);
