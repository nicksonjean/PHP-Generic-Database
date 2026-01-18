<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Build.php';
    class_alias('GenericDatabase\Core\Emulated\Build', 'GenericDatabase\Core\Build');
} else {
    require_once __DIR__ . '/Native/Build.php';
    class_alias('GenericDatabase\Core\Native\Build', 'GenericDatabase\Core\Build');
}
