<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Union.php';
    class_alias('GenericDatabase\Core\Emulated\Union', 'GenericDatabase\Core\Union');
} else {
    require_once __DIR__ . '/Native/Union.php';
    class_alias('GenericDatabase\Core\Native\Union', 'GenericDatabase\Core\Union');
}
