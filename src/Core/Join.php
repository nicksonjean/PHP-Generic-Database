<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Join.php';
    class_alias('GenericDatabase\Core\Emulated\Join', 'GenericDatabase\Core\Join');
} else {
    require_once __DIR__ . '/Native/Join.php';
    class_alias('GenericDatabase\Core\Native\Join', 'GenericDatabase\Core\Join');
}
