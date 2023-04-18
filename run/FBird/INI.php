<?php

use GenericDatabase\Engine\FBirdEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$fbird = FBirdEngine::new('../../assets/INI/fbird.ini')->connect();

var_dump($fbird);
