<?php

use
    GenericDatabase\Engine\OCIEngine,

    GenericDatabase\Engine\OCI\OCI;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$load = Dotenv\Dotenv::createImmutable(PATH_ROOT)->load();

$oci = new OCIEngine();
$oci->setHost($_ENV['OCI_HOST'])
    ->setPort(+$_ENV['OCI_PORT'])
    ->setDatabase($_ENV['OCI_DATABASE'])
    ->setUser($_ENV['OCI_USER'])
    ->setPassword($_ENV['OCI_PASSWORD'])
    ->setCharset('utf8')
    ->setOptions([
        OCI::ATTR_PERSISTENT => true,
        OCI::ATTR_CONNECT_TIMEOUT => 28800,
    ])
    ->setException(true)
    ->connect();

var_dump($oci);
