<?php

$var = getVars();

$extension = $var['extension'] ?? '';
$env = $var['env'] ?? '';
$label = $var['label'] ?? '';

if (extension_loaded($extension)) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'sqlite3') !== false) {
            return true;
        }
        return false;
    });

    try {
        $db = str_replace('../../', '../', $_ENV[$env . '_DATABASE']);

        if (!file_exists($db)) {
            throw new Exception('File is not exists in ' . $db);
        }

        $instance = $sqlite3 = new SQLite3(
            $db
        );

        if (!$instance instanceof SQLite3) {
            throw new Exception('Connection failed');
        }

        echo set_message('success', $label . ' Connected with sqlite3');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', $label . ' Not Connected with sqlite3');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'Extension ' . $extension . ' not loaded');
}
?>
<hr />
