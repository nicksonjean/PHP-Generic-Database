<?php

declare(strict_types=1);

namespace GenericDatabase\Core;

if (PHP_VERSION_ID < 80100) {
    require_once __DIR__ . '/Emulated/Limit.php';
} else {
    require_once __DIR__ . '/Native/Limit.php';
}
