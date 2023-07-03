<?php

use GenericDatabase\Engine\FBirdEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$fbird = FBirdEngine::new(PATH_ROOT . '/assets/INI/fbird.ini')->connect();

var_dump($fbird);
