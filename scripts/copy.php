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
if (isset($result['--source']) && isset($result['--dest'])) {
    copy($result['--source'], $result['--dest']);
    chmod($result['--dest'], 0755);
}
