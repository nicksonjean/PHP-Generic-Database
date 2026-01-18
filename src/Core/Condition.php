<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Condition.php';
    class_alias('GenericDatabase\Core\Emulated\Condition', 'GenericDatabase\Core\Condition');
} else {
    require_once __DIR__ . '/Native/Condition.php';
    class_alias('GenericDatabase\Core\Native\Condition', 'GenericDatabase\Core\Condition');
}
