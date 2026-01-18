<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Select.php';
    class_alias('GenericDatabase\Core\Emulated\Select', 'GenericDatabase\Core\Select');
} else {
    require_once __DIR__ . '/Native/Select.php';
    class_alias('GenericDatabase\Core\Native\Select', 'GenericDatabase\Core\Select');
}
