<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Query.php';
    class_alias('GenericDatabase\Core\Emulated\Query', 'GenericDatabase\Core\Query');
} else {
    require_once __DIR__ . '/Native/Query.php';
    class_alias('GenericDatabase\Core\Native\Query', 'GenericDatabase\Core\Query');
}
