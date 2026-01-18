<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Sorting.php';
    class_alias('GenericDatabase\Core\Emulated\Sorting', 'GenericDatabase\Core\Sorting');
} else {
    require_once __DIR__ . '/Native/Sorting.php';
    class_alias('GenericDatabase\Core\Native\Sorting', 'GenericDatabase\Core\Sorting');
}
