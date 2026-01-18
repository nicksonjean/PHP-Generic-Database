<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Column.php';
    class_alias('GenericDatabase\Core\Emulated\Column', 'GenericDatabase\Core\Column');
} else {
    require_once __DIR__ . '/Native/Column.php';
    class_alias('GenericDatabase\Core\Native\Column', 'GenericDatabase\Core\Column');
}
