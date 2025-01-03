<?php

$var = getVars();

$extension = $var['extension'] ?? '';
$env = $var['env'] ?? '';
$label = $var['label'] ?? '';

if (extension_loaded($extension)) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'oci_connect') !== false) {
            return true;
        }
        return false;
    });

    try {

        $instance = oci_connect(
            $_ENV[$env . '_USER'],
            $_ENV[$env . '_PASSWORD'],
            vsprintf('%s:%s/%s', [
                $_ENV[$env . '_HOST'],
                $_ENV[$env . '_PORT'],
                $_ENV[$env . '_DATABASE']
            ]),
            $_ENV[$env . '_CHARSET']

        );

        if (!$instance) {
            throw new Exception('Connection failed');
        }

        echo set_message('success', $label . ' Connected with oci_connect');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', $label . ' Not Connected with oci_connect');
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
