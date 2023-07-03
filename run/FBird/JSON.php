<?php

use GenericDatabase\Engine\FBirdEngine;

define("PATH_ROOT", dirname(dirname(__DIR__)));

require_once PATH_ROOT . '/vendor/autoload.php';

$fbird = FBirdEngine::new(PATH_ROOT . '/assets/JSON/fbird.json')->connect();

var_dump($fbird);
