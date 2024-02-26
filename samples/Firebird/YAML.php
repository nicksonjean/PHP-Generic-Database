<?php

use GenericDatabase\Engine\FirebirdEngine;

define("PATH_ROOT", dirname(__DIR__, 2));

require_once PATH_ROOT . '/vendor/autoload.php';

$firebird = FirebirdEngine::new(PATH_ROOT . '/resources/dsn/yaml/firebird.yaml')->connect();

var_dump($firebird);
