<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Junction.php';
    class_alias('GenericDatabase\Core\Emulated\Junction', 'GenericDatabase\Core\Junction');
} else {
    require_once __DIR__ . '/Native/Junction.php';
    class_alias('GenericDatabase\Core\Native\Junction', 'GenericDatabase\Core\Junction');
}
