<?php

$result = [];
foreach ($argv as $arg) {
    $e = explode("=", $arg);
    if (count($e) === 2) {
        $result[$e[0]] = $e[1];
    } else {
        $result[$e[0]] = 0;
    }
}
if (isset($result['--dest']) && is_dir($result['--dest'])) {
    $result['--dest'] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $result['--dest']);
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = sprintf('rmdir /s /q %s', $result['--dest']);
    } else {
        $cmd = sprintf('rm -rf %s', $result['--dest']);
    }
    exec($cmd);
}
