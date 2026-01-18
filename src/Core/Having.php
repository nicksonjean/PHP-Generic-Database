<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Having.php';
    class_alias('GenericDatabase\Core\Emulated\Having', 'GenericDatabase\Core\Having');
} else {
    require_once __DIR__ . '/Native/Having.php';
    class_alias('GenericDatabase\Core\Native\Having', 'GenericDatabase\Core\Having');
}
