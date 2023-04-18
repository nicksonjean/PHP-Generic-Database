<?php

use GenericDatabase\Engine\FBirdEngine;

require_once __DIR__ . '/../../vendor/autoload.php';

$fbird = FBirdEngine::new('../../assets/XML/fbird.xml')->connect();

var_dump($fbird);
