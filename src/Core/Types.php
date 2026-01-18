<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Types.php';
    class_alias('GenericDatabase\Core\Emulated\Types', 'GenericDatabase\Core\Types');
} else {
    require_once __DIR__ . '/Native/Types.php';
    class_alias('GenericDatabase\Core\Native\Types', 'GenericDatabase\Core\Types');
}
