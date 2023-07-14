<?php

namespace GenericDatabase\Engine\OCI;

class Dump
{
    public static function loadFromFile(string $file, string $delimiter = ';', ?callable $onProgress = null): int
    {
        var_dump($file, $delimiter, $onProgress);
        return 0;
    }
}
