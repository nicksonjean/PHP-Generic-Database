<?php

use GenericDatabase\Engine\YAMLConnection;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$yaml = YAMLConnection::new(PATH_ROOT . '/resources/dsn/yaml/yaml.yaml')->connect();

var_dump($yaml);
